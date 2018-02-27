<?php

namespace App\Listeners;

use App\Constants\Events;
use App\Constants\HTTPProxyMode;
use App\Constants\ServiceType;
use App\Events\NodeEvent;
use App\Libraries\HTTPProxyManager;
use App\Libraries\NodeManager;
use App\Libraries\Utility;
use App\Mail\NodeVerificationFailed;
use App\Mail\ProxyVerificationFailed;
use App\Mail\ResourceConfigFailed;
use App\Service;
use App\ServiceIPAddress;
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
     * @param  NodeEvent  $event
     * @return void
     */
    public function handle(NodeEvent $event)
    {
        $node = $event->node;
        $oldState = Utility::getPreviousModel($event->dataBag);
        $error = Utility::getError($event->dataBag);

        $servicesData = array();
        $servicesIpData = array();

        switch ($event->type)
        {
            case Events::NODE_CREATED:
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

                unset($data['systemConfig']);

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
                                Mail::to($node->user->email)->queue(new ResourceConfigFailed($errors));
                                break;
                            }

                            $proxyManager = new HTTPProxyManager();
                            list($authKey, $password) = explode(':', $node->access_token, 2);
                            foreach ($resource['connectionResource']['accessReference'] as $index => $reference)
                            {
                                list($ip, $port) = explode(':', $reference, 2);
                                $verified = $proxyManager->verify($ip, $ip, $port, $authKey, $password);

                                if (!$verified)
                                {
                                    Mail::to($node->user->email)->queue(new ProxyVerificationFailed());
                                    break;
                                }
                            }

                            $newService = new Service();
                            $servicesData[] = [
                                'node_id' => $node->id,
                                'type' => $service,
                                'config' => json_encode($config),
                                'connection_resource' => json_encode($resource['connectionResource'])
                            ];

                            $servicesIpData[] = [
                                'ip' => $ip,
                                'type' => $service,
                                'service_id' => $newService->id,
                            ];



                            /*
                             * TODO: if invalid, email user why and bail.
                             * if valid, see https://paste.ee/p/rW4G6#61DhWNCU1JtXz6GHouDQxJfndTtsLefy for schema
                             * verify resource->connectionResource->accessReference (all on a loop) with the HTTPProxyManager, they ALL need to pass validation. If failed, again, mail user why
                             * If that passes, create a new service with these details. See migration for schema, self explanatory. Apply json_encode when needed
                             * Then create ServiceIPAddresses (one for each accessReference ip), see migration for schema again
                             */

                            break;
                        case ServiceType::OpenVPN:
                            break;
                    }
                }

                DB::transaction(function() use ($servicesData, $servicesIpData) {
                    \DB::table('services')->insert($servicesData);
                    \DB::table('service_ip_address')->insert($servicesIpData);
                });
                
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
