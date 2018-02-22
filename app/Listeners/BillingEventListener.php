<?php


namespace App\Listeners;


use App\Constants\Events;
use App\Constants\InvoiceStatus;
use App\Constants\PaymentType;
use App\Events\BillingEvent;
use App\Invoice;
use App\Libraries\Utility;
use App\Mail\InvoicePaid;
use App\Order;
use Illuminate\Support\Facades\Mail;

class BillingEventListener extends BaseListener
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

                /** @var Invoice $invoice */
                $invoice = $object->invoice;
                switch ($object->type)
                {
                    case PaymentType::CREDIT:
                        if ($invoice->amount - $invoice->transactions->sum('amount') <= 0)
                        {
                            // Invoice can now be marked as paid, activate any associated orders
                            $invoice->status = InvoiceStatus::PAID;
                            $invoice->saveOrFail();

                            $user = $invoice->user;

                            Mail::to($user->email)->queue(new InvoicePaid($invoice));

                            // TODO: perform order activation here
                        }
                        break;

                    case PaymentType::DEBIT:
                        // TODO: terminate/cancel stuff if refunds happen
                }

                // TODO: communicate with xero/whoever to accurately account
            break;
            case Events::ORDER_CREATED:
                // The object is an order in this case
                // TODO: Send the user an e-mail confirmation that their order has been successfully lodged.

                /** @var Order $order */
                $order = $event->data;


        }
    }
}