<?php

namespace App\Listeners;

use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\ServiceType;
use App\Errors\FatalException;
use App\Errors\UserFriendlyException;
use App\Events\NodeEvent;
use App\Libraries\NodeManager;
use App\Libraries\Utility;

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

        \Log::info($node);

        switch ($event->type)
        {
            case Events::NODE_CREATED:
                // Great, let's actually attempt to discover this node's services
                $manager = new NodeManager($node);
                $data = $manager->firstTimeDiscovery();

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
                // TODO: Send App\Mail\NodeVerificationFailed() to the node's owner
                \Log::info($error);
                break;
        }
    }
}
