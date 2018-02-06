<?php


namespace App\Listeners;


use App\Constants\Events;
use App\Events\BillingEvent;
use App\Libraries\Utility;

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
        $object = $event->data;
        $oldState = Utility::getPreviousModel($event->dataBag);
        $error = Utility::getError($event->dataBag);

        switch ($event->type)
        {
            case Events::BILLING_TRANSACTION_ADDED:
                // The object is a transaction in this case
                // Figure out what type it is, and:
                // Update status + communicate with 3rd party accounting

        }
    }
}