<?php

namespace App\Jobs;

use App\Constants\InvoiceStatus;
use App\Constants\OrderStatus;
use App\Libraries\BillingUtils;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecurringInvoiceHandlingJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $orders = DB::table('orders')
            ->where('status', OrderStatus::ACTIVE)
            ->get();

        foreach ($orders as $order)
        {
            $due_next = Carbon::parse($order->due_next);
            if ($due_next->subDays(env('EARLY_INVOICE_GENERATION_DAYS')) <= Carbon::now()
                && $order->lastInvoice == InvoiceStatus::PAID)
                BillingUtils::createInvoice($order, Carbon::parse($order->due_next)->addMonth());
        }
    }
}
