<?php

namespace App\Listeners;

use App\Constants\Events;
use App\Events\UserEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserEventListener extends BaseListener
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
     * @param  "UserEvent"  $event
     * @return void
     */
    public function handle(UserEvent $event)
    {
        // TODO: actually take care of all this.
        // TODO: Created/Updated should take care of email verification

        switch ($event->type)
        {
            case Events::USER_CREATED:
                break;
            case Events::USER_UPDATED:
                break;
            case Events::USER_DELETED:
                break;
        }
    }
}
