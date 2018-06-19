<?php

namespace App\Jobs;

use App\Constants\InvoiceStatus;
use App\Constants\OrderStatus;
use App\Libraries\BillingUtils;
use App\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        $bufferDays = env('EARLY_INVOICE_GENERATION_DAYS', 7) + 1;

        $now = Carbon::now();

        // Querying for future dates, mind the TIMESTAMPDIFF param order (it's param 2 - param 1)
        // Read it like this: due_next is either in the past, or up to $bufferDays in the future.
        $orders = Order::where('status', OrderStatus::ACTIVE)
            ->whereRaw("TIMESTAMPDIFF(DAY, '$now', due_next) <= '$bufferDays'")
            ->get();

        foreach ($orders as $order)
        {
            // If the last invoice is paid, means another was NOT generated yet. At the same time, due_next is valid, the two conditions required for a new invoice to be generated.
            // The invoice is due on the EXACT SAME DAY that the order is.
            if ($order->lastInvoice->status == InvoiceStatus::PAID)
                BillingUtils::createInvoice($order, $order->due_next);
        }
    }
}
