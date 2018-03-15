<?php

namespace App\Jobs;

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
        $orders = Order::where('status', OrderStatus::ACTIVE)->get();
        foreach ($orders as $order)
        {
            $due_next = Carbon::parse($order->due_next);
            $now = Carbon::now();
            if ( $due_next->diffInDays($now) > env('TERMINATE_AFTER_OVERDUE_DAYS')  )
            {
                BillingUtils::cancelOrder($order);
                $lastInvoice = $order->lastInvoice;
                $lastInvoice->status = OrderStatus::CANCELLED;
                $lastInvoice->saveOrFail();
            }
            Mail::to($order->user->email)->queue(new OrderTerminated($order));
        }
    }
}
