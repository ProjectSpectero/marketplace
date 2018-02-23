<?php


namespace App\Listeners;


use App\Constants\Events;
use App\Constants\InvoiceStatus;
use App\Constants\OrderStatus;
use App\Constants\PaymentType;
use App\Events\BillingEvent;
use App\Invoice;
use App\Libraries\FraudCheckManager;
use App\Libraries\Utility;
use App\Mail\InvoicePaid;
use App\Mail\OrderCreated;
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
                /** @var Order $order */
                $order = $event->data;

                $user = $order->user;

                // Now, let's verify that the order passes standard fraud checks (assuming the relevant status exists)
                if ($order->status == OrderStatus::AUTOMATED_FRAUD_CHECK)
                {
                    if (FraudCheckManager::verify($order))
                        $order->status = OrderStatus::PENDING;
                    else
                        $order->status = OrderStatus::MANUAL_FRAUD_CHECK;

                    $order->saveOrFail();
                }

                // Let's notify our user and confirm that their order has been placed.
                // This email also notifies them if they failed the fraud check
                // If they passed, it asks them to make payment.
                Mail::to($user->email)->queue(new OrderCreated($order));

            break;


        }
    }
}