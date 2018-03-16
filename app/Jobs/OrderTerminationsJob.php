<?php

namespace App\Jobs;

use App\Constants\InvoiceStatus;
use App\Constants\OrderStatus;
use App\Libraries\BillingUtils;
use App\Mail\OrderTerminated;
use App\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class OrderTerminationsJob extends BaseJob
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
        $now = Carbon::now();
        $overdueDays = env('TERMINATE_AFTER_OVERDUE_DAYS', 7) + 1;
        $orders = Order::where('status', OrderStatus::ACTIVE)
            ->whereRaw("TIMESTAMPDIFF(DAY, due_next, '$now') > '$overdueDays'")
            ->get();

        foreach ($orders as $order)
        {
            if ($order->lastInvoice->status == InvoiceStatus::UNPAID)
            {
                BillingUtils::cancelOrder($order);
                $lastInvoice = $order->lastInvoice;
                $lastInvoice->status = OrderStatus::CANCELLED;
                $lastInvoice->saveOrFail();
            }
            else
                \Log::warning("Invoice is paid but due_next its still in the past", $order->toArray());


            Mail::to($order->user->email)->queue(new OrderTerminated($order));
        }
    }
}
