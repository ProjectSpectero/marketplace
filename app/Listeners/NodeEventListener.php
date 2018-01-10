<?php

namespace App\Listeners;

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
        //
    }
}
