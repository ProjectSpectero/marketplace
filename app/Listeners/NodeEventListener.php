<?php

namespace App\Listeners;

use App\Constants\Events;
use App\Constants\HTTPProxyMode;
use App\Constants\NodeStatus;
use App\Constants\ServiceType;
use App\Events\NodeEvent;
use App\Libraries\GeoIPManager;
use App\Libraries\HTTPProxyManager;
use App\Libraries\NodeManager;
use App\Libraries\Utility;
use App\Mail\NodeVerificationFailed;
use App\Mail\NodeVerificationSuccessful;
use App\Mail\ProxyVerificationFailed;
use App\Mail\ResourceConfigFailed;
use App\Node;
use App\NodeIPAddress;
use App\Service;
use DB;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Validator;

class NodeEventListener extends BaseListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    private function updateNodeStatus (Node $node, String $newStatus)
    {
        if ($node->status !== $newStatus)
        {
            $node->status = $newStatus;
            $node->saveOrFail();
        }
    }

    /**
     * Handle the event.
     *
     * @param  NodeEvent $event
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function handle(NodeEvent $event)
    {
        $node = $event->node;
        $oldState = Utility::getPreviousModel($event->dataBag);
        $error = Utility::getError($event->dataBag);

        switch ($event->type)
        {
            case Events::NODE_REVERIFY:
                // Idea is simple, we condition the node for re-verification and then run it through the newly created screen again.
                // Note the missing break!
                $node->ipAddresses()->delete();
                $node->services()->delete();
            case Events::NODE_CREATED:
                // Let's first check if the node is actually in NEED of verification. We don't do anything if it's confirmed.
                if ($node->status == NodeStatus::CONFIRMED)
                    return;

                if ($node->status == NodeStatus::PENDING_VERIFICATION)
                {
                    // Reset it out of that status, since verification has now been attempted.
                    $node->status = NodeStatus::UNCONFIRMED;
                    $node->saveOrFail();
                }

                // Great, let's actually attempt to discover this node's services
                /*
                 * Flow:
                 * Verify connectivity
                 * Verify auth
                 * Verify node config
                 * Verify service config
                 * Lookup ASN and CC
                 * Insert it all accordingly
                 */
                $manager = new NodeManager($node);
                $data = $manager->discover(true);
                $userEmail = $node->user->email;

                // If this happens, the verification failed event has already been fired. We can just gracefully quit.
                if ($data == null)
                    return;

                if (empty($data['services']))
                {
                    Mail::to($userEmail)->queue(new ProxyVerificationFailed($node, 'Service discovery: no services could be found'));
                    $this->updateNodeStatus($node, NodeStatus::UNCONFIRMED);

                    return;
                }

                // OK, we managed to talk to the daemon and got the data.
                // If we got here, it also means that the daemon's configs are as we expect it to be (otherwise NODE_VERIFICATION_FAILED has been fired)

                $serviceCollection = [];

                // Now let us iterate and validate each service is really what it claims to be.
                foreach ($data['services'] as $service => $resource)
                {
                    switch ($service)
                    {
                        case ServiceType::HTTPProxy:
                            // First, let us account for the proxy mode, and the count(s) of the allowed/banned domains
                            // We validate EVERY proxy here to confirm that a DISTINCT outgoing IP is available for each.
                            $config = $resource['config'];

                            $rules = [
                                'listeners' => 'array|min:1',
                                'listeners.*.item1' => 'required|ip',
                                'listeners.*.item2' => 'required|min:1024|max:65534',
                                'proxyMode' => Rule::in(HTTPProxyMode::getConstants())
                            ];

                            $validator = Validator::make($config, $rules);

                            if ($validator->fails())
                            {
                                $errors = $validator->errors()->getMessages();
                                // Needs details on what the node was (preferably a link) along with a link to retrying the verification after fixing it
                                Mail::to($userEmail)->queue(new ResourceConfigFailed($node, $errors));
                                $this->updateNodeStatus($node, NodeStatus::UNCONFIRMED);
                                return;
                            }

                            $newService = new Service();
                            $newService->node_id = $node->id;
                            $newService->type = $service;
                            $newService->config = $config;
                            $newService->connection_resource = $resource['connectionResource'];

                            $proxyManager = new HTTPProxyManager();
                            list($authKey, $password) = explode(':', $node->access_token, 2);

                            $outgoingIpCollection = [];

                            foreach ($resource['connectionResource']['accessReference'] as $index => $reference)
                            {
                                $parts = explode(':', $reference);

                                $port = array_pop($parts);
                                $ip = implode(':', $parts);

                                // TODO: build support for verifying IPv6 proxies too, currently skipped.
                                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false)
                                    continue;

                                try
                                {
                                    $outgoingIp = $proxyManager->discover($ip, $port, $authKey, $password);
                                }
                                catch (RequestException $exception)
                                {
                                    $outgoingIp = false;
                                }

                                if ($outgoingIp == false)
                                {
                                    Mail::to($userEmail)->queue(new ProxyVerificationFailed($node, $ip, "Resolution: could not resolve outgoing IP for proxy $reference."));
                                    $this->updateNodeStatus($node, NodeStatus::UNCONFIRMED);
                                    return;
                                }

                                // This ensures the duplicate verification (i.e: multiple defined proxies, but with the same outgoing IP)
                                if (in_array($outgoingIp, $outgoingIpCollection))
                                {
                                    // Duplicate, proxies NEED to have unique IPs.
                                    Mail::to($userEmail)->queue(new ProxyVerificationFailed($node, $ip, "Duplicate: the outgoing IP of $outgoingIp has been seen before (encountered when verifying $reference)."));
                                    $this->updateNodeStatus($node, NodeStatus::UNCONFIRMED);
                                    return;
                                }

                                // This would mean it's an IP that our discoverer did not find, was likely manually configured into the HTTPProxy service instead.
                                // Regardless, all good with us though, we want to know about it.
                                if (! in_array($outgoingIp, $data['ipAddresses']))
                                    $data['ipAddresses'][] = $outgoingIp;

                                $outgoingIpCollection[] = $outgoingIp;
                            }

                            // No further use, get rid of it to conserve memory.
                            unset($outgoingIpCollection);

                            $serviceCollection[] = $newService;

                            break;
                        case ServiceType::OpenVPN:
                            $newService = new Service();
                            $newService->node_id = $node->id;
                            $newService->type = $service;
                            $newService->config = $resource['config'];
                            $newService->connection_resource = $resource['connectionResource'];

                            $serviceCollection[] = $newService;

                            break;
                    }
                }

                $ipCollection = [];
                foreach ($data['ipAddresses'] as $ipAddress)
                {
                    if (NodeIPAddress::where('ip', $ipAddress)->count())
                    {
                        Mail::to($userEmail)->queue(new ProxyVerificationFailed($node, $node->ip, "Duplicate IP of $ipAddress found elsewhere, please open a support ticket. Automatic verification not possible."));
                        $this->updateNodeStatus($node, NodeStatus::UNCONFIRMED);
                        return;
                    }

                    // OK bob, it doesn't exist. Let's go look stuff up
                    $geo = GeoIPManager::resolve($ipAddress);
                    $ipCollection[] = [
                        'ip' => $ipAddress,
                        'cc' => $geo['cc'],
                        'asn' => $geo['asn'],
                        'city' => $geo['city']
                    ];
                }

                // Free up memory.
                unset($data['ipAddresses']);

                // Let's discover some fundamentals about the node IP itself too.
                $geoData = GeoIPManager::resolve($node->ip);

                // We need to mark the node for a re-verify if anything goes wrong here (automated)
                DB::transaction(function() use ($serviceCollection, $node, $ipCollection, $geoData, $data)
                {
                    foreach ($serviceCollection as $holder)
                    {
                        /** @var Service $service */
                        $service = $holder;
                        $service->saveOrFail();
                    }

                    foreach ($ipCollection as $ipDetails)
                    {
                        // That means this thing is a IPv6 IP
                        // TODO: Add support for proper accounting + distinguishment of IPv6 IPs at a later date.
                        if (filter_var($ipDetails['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false)
                            continue;

                        $persistedIp = new NodeIPAddress();
                        $persistedIp->ip = $ipDetails['ip'];
                        $persistedIp->node_id = $node->id;
                        $persistedIp->cc = $ipDetails['cc'];
                        $persistedIp->city = $ipDetails['city'];
                        $persistedIp->asn = $ipDetails['asn'];
                        $persistedIp->saveOrFail();
                    }

                    // If everything went well, node is now confirmed.
                    $node->app_settings = $data['appSettings'];
                    $node->system_config = $data['systemConfig'];

                    $node->cc = $geoData['cc'];
                    $node->asn = $geoData['asn'];
                    $node->city = $geoData['city'];

                    $node->status = NodeStatus::CONFIRMED;
                    $node->saveOrFail();
                });

                Mail::to($userEmail)->queue(new NodeVerificationSuccessful($node));
                
                break;
            case Events::NODE_UPDATED:
                break;
            case Events::NODE_DELETED:
                break;
            case Events::NODE_CONFIG_INVALID:
                break;
            case Events::NODE_REACHABLE:
                break;
            case Events::NODE_UNREACHABLE:
                break;
            case Events::NODE_VERIFICATION_FAILED:
                Mail::to($node->user->email)->queue(new NodeVerificationFailed($node, $error));
                break;
        }
    }
}
