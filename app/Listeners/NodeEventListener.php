<?php

namespace App\Listeners;

use App\Constants\Events;
use App\Events\NodeEvent;
use App\Libraries\NodeManager;
use App\Libraries\Utility;
use App\Mail\NodeVerificationFailed;
use Illuminate\Support\Facades\Mail;

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
                $node->system_config = json_encode($data['systemConfig']);
                $node->saveOrFail();

                // Now let us iterate and validate each service is really what it claims to be.








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
