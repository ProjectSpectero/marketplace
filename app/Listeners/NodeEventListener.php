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
use App\Service;
use App\ServiceIPAddress;
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
            case Events::NODE_CREATED:
                // Let's first check if the node is actually in NEED of verification. We don't do anything if it's confirmed.
                if ($node->status == NodeStatus::CONFIRMED)
                    return;

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
                // OK, we managed to talk to the daemon and got the data.
                // If we got here, it also means that the daemon's configs are as we expect it to be (otherwise NODE_VERIFICATION_FAILED has been fired)

                // Let's save them.
                $node->loaded_config = json_encode($data['systemConfig']);
                $node->saveOrFail();

                $userEmail = $node->user->email;

                unset($data['systemConfig']);

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
                                return;
                            }

                            $newService = new Service();
                            $newService->node_id = $node->id;
                            $newService->type = $service;
                            $newService->config = json_encode($config);
                            $newService->connection_resource = json_encode($resource['connectionResource']);

                            $proxyManager = new HTTPProxyManager();
                            list($authKey, $password) = explode(':', $node->access_token, 2);

                            $outgoingIpCollection = [];

                            foreach ($resource['connectionResource']['accessReference'] as $index => $reference)
                            {
                                list($ip, $port) = explode(':', $reference, 2);

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
                                    Mail::to($userEmail)->queue(new ProxyVerificationFailed($node, $ip, "Resolution: could not resolve outgoing IP for proxy $ip:$port."));
                                    return;
                                }

                                if (in_array($outgoingIp, $outgoingIpCollection))
                                {
                                    // Duplicate, proxies NEED to have unique IPs.
                                    Mail::to($userEmail)->queue(new ProxyVerificationFailed($node, $ip, "Duplicate: the outgoing IP of $outgoingIp has been seen before."));
                                    return;
                                }

                                $outgoingIpCollection[] = $outgoingIp;
                            }

                            $serviceCollection[] = [
                                'service' => $newService,
                                'ips' => $outgoingIpCollection
                            ];

                            break;
                        case ServiceType::OpenVPN:
                            break;
                    }
                }

                // Let's discover some fundamentals about the IP itself
                $geoData = GeoIPManager::resolve($node->ip);

                // We need to mark the node for a re-verify if anything goes wrong here (automated)
                DB::transaction(function() use ($serviceCollection, $node, $geoData)
                {
                    foreach ($serviceCollection as $holder)
                    {
                        /** @var Service $service */
                        $service = $holder['service'];

                        /** @var array $ipCollection */
                        $ipCollection = $holder['ips'];

                        $service->saveOrFail();

                        // NOW, $service has an ID associated.

                        foreach ($ipCollection as $ip)
                        {
                            $persistedIp = new ServiceIPAddress();
                            $persistedIp->ip = $ip;
                            $persistedIp->type = $service->type;
                            $persistedIp->service_id = $service->id;
                            $persistedIp->saveOrFail();
                        }
                    }

                    // If everything went well, node is now confirmed.
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
            case Events::NODE_REVERIFY:
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
