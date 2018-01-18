<?php


namespace App\Listeners;

use App\Events\FraudCheckEvent;

class FraudCheckListener extends BaseListener
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
     * @param  FraudCheckEvent  $event
     * @return void
     */
    public function handle(FraudCheckEvent $event)
    {
        //
    }
}