<?php

namespace App\Listeners;

use App\Constants\Events;
use App\Events\NodeEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class NodeEventListener implements ShouldQueue
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
        switch ($event->type)
        {
            case Events::NODE_CREATED:
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
        }
    }
}
