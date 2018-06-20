<?php

namespace App\Jobs;

use App\Constants\InvoiceStatus;
use App\Constants\OrderStatus;
use App\Libraries\BillingUtils;
use App\Mail\NewInvoiceGeneratedMail;
use App\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class RecurringInvoiceHandlingJob extends BaseJob
{
    protected $signature = "invoice:generate";
    protected $description = "Generate invoices for orders based on their term.";
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Throwable
     */
    public function handle()
    {
        // Why +1? Because 0 is a valid index.
        $bufferDays = env('EARLY_INVOICE_GENERATION_DAYS', 14) + 1;

        $now = Carbon::now();

        // Querying for future dates, mind the TIMESTAMPDIFF param order (it's param 2 - param 1)
        // Read it like this: due_next is either in the past, or up to $bufferDays in the future.
        $orders = Order::where('status', OrderStatus::ACTIVE)
            ->whereRaw("TIMESTAMPDIFF(DAY, '$now', due_next) <= $bufferDays")
            ->get();

        $count = count($orders);

        \Log::info("Found $count possible order(s) to generate renewal invoice(s) for.");

        foreach ($orders as $order)
        {
            $lastInvoice = $order->lastInvoice;

            // If the last invoice is paid, means another was NOT generated yet. At the same time, due_next is valid, the two conditions required for a new invoice to be generated.
            // The invoice is due on the EXACT SAME DAY that the order is.
            if ($lastInvoice->status == InvoiceStatus::PROCESSING)
            {
                \Log::warn("Encountered invoice #$lastInvoice->id (due on $lastInvoice->due_date) in PROCESSING status.
                    Skipping generating new invoice (despite order due_next being $order->due_next), will retry on next cycle...");
                continue;
            }
            if ($lastInvoice->status == InvoiceStatus::PAID)
            {
                // OK, the last one is paid. This means that no new invoices have been generated yet.
                $newInvoice = BillingUtils::createInvoice($order, $order->due_next);

                \Log::info("Order #$order->id: generated invoice #$newInvoice->id due on $newInvoice->due_date for renewal.");

                // Now, let's notify the user that this invoice has been generated.
                // TODO: actually brief them on the condition of their stored payment method, or account credit (i.e: will it be enough, or is further action required)?
                Mail::to($lastInvoice->user->email)->queue(new NewInvoiceGeneratedMail($newInvoice));
            }
            else
                \Log::info("Skipping order #$order->id (due on: $order->due_next) as its last invoice is still yet to be paid. Last invoice: (#$lastInvoice->id, due on: $lastInvoice->due_date, status: $lastInvoice->status)");

        }
    }
}
