<?php

namespace App\Listeners;

use App\Constants\Events;
use App\Events\NodeEvent;
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

        switch ($event->type)
        {
            case Events::NODE_CREATED:
                // Great, let's actually attempt to discover this node's services
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
                break;
        }
    }
}
