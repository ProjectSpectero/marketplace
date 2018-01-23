<?php


namespace App\Listeners;


use App\Events\BillingEvent;

class BillingListener extends BaseListener
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
    public function handle(BillingEvent $event)
    {
        //
    }
}