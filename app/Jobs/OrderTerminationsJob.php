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
    protected $signature = "order:terminate-overdue";
    protected $description = "Terminate overdue order(s).";

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
                Mail::to($order->user->email)->queue(new OrderTerminated($order));
            }
            else
                \Log::warning("Invoice is paid but due_next its still in the past", $order->toArray());
        }
    }
}
