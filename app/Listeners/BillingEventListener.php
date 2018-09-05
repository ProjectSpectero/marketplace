<?php


namespace App\Listeners;


use App\Constants\Events;
use App\Constants\InvoiceStatus;
use App\Constants\InvoiceType;
use App\Constants\OrderStatus;
use App\Constants\PaymentType;
use App\Events\BillingEvent;
use App\Invoice;
use App\Libraries\AccountingManager;
use App\Libraries\BillingUtils;
use App\Libraries\FraudCheckManager;
use App\Libraries\TaxationManager;
use App\Libraries\Utility;
use App\Mail\InvoicePaid;
use App\Mail\OrderCreated;
use App\Mail\OrderProvisionedMail;
use App\Mail\OrderTrippedFraudAlertMail;
use App\Order;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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
     * @param  FraudCheckEvent $event
     * @return void
     * @throws \Throwable
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

                /** @var User $user */
                $user = $invoice->user;

                switch ($object->type)
                {
                    case PaymentType::CREDIT:
                        $currentDueAmount = BillingUtils::getInvoiceDueAmount($invoice);

                        // This checks if the invoice is FULLY paid.
                        if ($currentDueAmount <= 0)
                        {
                            // Invoice can now be marked as paid, activate any associated orders
                            $invoice->status = InvoiceStatus::PAID;
                            $invoice->saveOrFail();

                            // There is a slight chance of this ending up re-activating a cancelled order if the listener runs after a delay.
                            if ($invoice->type == InvoiceType::STANDARD
                            && $invoice->order != null)
                            {
                                /** @var Order $order */
                                $order = $invoice->order;

                                if (in_array($order->status, OrderStatus::getPayable()))
                                {
                                    switch ($order->status)
                                    {
                                        case OrderStatus::ACTIVE:
                                            // This means we're basically renewing it.
                                            $newDueNext = $order->due_next->addDays($order->term);
                                            Log::info("Renewing Order #$order->id: due date advanced from $order->due_next to $newDueNext ($order->term days)");

                                            $order->due_next = $newDueNext;

                                            break;

                                        case OrderStatus::PENDING:
                                            // This means this is the first activation.
                                            if (! FraudCheckManager::stageTwo($order))
                                            {
                                                // Boo, order failed the fraud check.
                                                $order->status = OrderStatus::MANUAL_FRAUD_CHECK;
                                                Log::warning("Order #" . $order->id . ' failed the stage two fraud check!');

                                                Mail::to($user->email)->queue(new OrderTrippedFraudAlertMail($order));

                                                // TODO: Raise a ticket with the customer internally in FreshDesk to track the fraud-check failure.
                                            }
                                            else
                                            {
                                                $newDueNext = Carbon::now()->addDays($order->term);
                                                Log::info("First Activation of Order #$order->id: due date advanced from $order->due_next to $newDueNext ($order->term days)");

                                                $order->due_next = $newDueNext;
                                                $order->status = OrderStatus::ACTIVE;

                                                foreach ($order->lineItems as $item)
                                                {
                                                    $item->status = OrderStatus::ACTIVE;
                                                    $item->saveOrFail();
                                                }

                                                Mail::to($user->email)->queue(new OrderProvisionedMail($order));

                                            }

                                            break;
                                    }

                                    // Update the order's record on the DB all the same since both branches modify it.
                                    $order->saveOrFail();
                                }
                            }

                            // There's no partial credit add, invoice needs to be fully paid for this to happen
                            if ($invoice->type == InvoiceType::CREDIT)
                            {
                                // Well now, user paid a credit add invoice. Let's add him his credit, shall we?
                                // TODO: make this multi-currency aware someday.

                                $user->credit = $user->credit + $object->amount; // Currency is assumed to be USD
                                $user->saveOrFail();
                            }
                        }
                        else if ($currentDueAmount > 0 && $currentDueAmount < $invoice->amount)
                        {
                            // TODO: Figure out the multi-currency impact here someday.
                            // This block is what transitions the invoice out of a 'processing' status even if it's not fully paid.

                            $invoice->status = InvoiceStatus::PARTIALLY_PAID;
                            $invoice->saveOrFail();
                        }

                        // Acknowledgement goes out whether the invoice is paid in full or not.
                        Mail::to($user->email)->queue(new InvoicePaid($invoice, $object));
                        break;

                    case PaymentType::DEBIT:
                        // TODO: terminate/cancel stuff if refunds happen
                        break;
                }
                AccountingManager::account($object);

            break;
            case Events::ORDER_CREATED:
                // The object is an order in this case
                /** @var Order $order */
                $order = $event->data;

                $user = $order->user;

                // Let's notify our user and confirm that their order has been placed.
                Mail::to($user->email)->queue(new OrderCreated($order));

            break;

            // TODO: Assess its usage, and ensure that this is really needed
            // BillingUtils::cancelOrder does this already.
            case Events::ORDER_REVERIFY:
                // The object is an order in this case
                /** @var Order $order */
                $order = $event->data;

                // The first thing we do is fix the invoice if it needs fixing, this is mostly called when an engagement / the whole order is cancelled.
                // Cancel the invoice too if order gets cancelled.
                /** @var Invoice $lastInvoice */
                $lastInvoice = $order->lastInvoice;

                if ($order->status == OrderStatus::CANCELLED)
                {
                    foreach ($order->lineItems as $item)
                    {
                        $item->status = OrderStatus::CANCELLED;
                        $item->saveOrFail();
                    }

                    if ($lastInvoice->status != InvoiceStatus::PAID)
                    {
                        $lastInvoice->status = InvoiceStatus::CANCELLED;
                        $lastInvoice->saveOrFail();
                    }

                    return;
                }

                // Let's verify that the amount is correct (perhaps partial cancellation)

                $amount = BillingUtils::getOrderDueAmount($order);
                $tax = TaxationManager::getTaxAmount($order, $amount);
                $amount += $tax;

                // TODO: make this currency aware, currently we're operating on the assumption that everything is USD.
                // We ONLY make changes if invoice isn't paid. Otherwise it'll be picked up from the next one.

                if ($lastInvoice->status != InvoiceStatus::PAID && $lastInvoice->amount != $amount)
                {
                    $lastInvoice->amount = $amount;
                    $lastInvoice->tax = $tax;

                    $lastInvoice->saveOrFail();
                }

                break;
        }
    }
}