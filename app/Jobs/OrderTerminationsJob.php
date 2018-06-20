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

        // Why +1? Because 0 is a valid index.
        $overdueDays = env('TERMINATE_AFTER_OVERDUE_DAYS', 7) + 1;

        $orders = Order::where('status', OrderStatus::ACTIVE)
            ->whereRaw("TIMESTAMPDIFF(DAY, due_next, '$now') > $overdueDays")
            ->get();

        $count = count($orders);

        \Log::info("Found $count order(s) to TERMINATE, proceeding ahead.");

        /** @var Order $order */
        foreach ($orders as $order)
        {
            // Enterprise orders are exempt from auto termination;
            // TODO: Get ent going, and remove this limitation.
            if ($order->isEnterprise())
            {
                \Log::warn("Order #$order->id: this is an enterprise order, termination skipped. Please fish for payment.");
                continue;
            }


            $lastInvoice = $order->lastInvoice;

            if ($lastInvoice->status == InvoiceStatus::UNPAID)
            {
                if (BillingUtils::getInvoiceDueAmount($lastInvoice) <= 0)
                {
                    \Log::warn("Order #$order->id: invoice #$lastInvoice->id is marked as UNPAID, but has no dues. Transactions exist.");
                    continue;
                }

                \Log::info("Order #$order->id: Auto-cancelling, it was due on $order->due_next.");

                BillingUtils::cancelOrder($order);
                Mail::to($order->user->email)->queue(new OrderTerminated($order));
            }
            else
                \Log::warning("Order #$order->id: Invoice (#$lastInvoice->id) status is $lastInvoice->status (due on $lastInvoice->due_date), but due_next ($order->due_next) is still in the past.");
        }
    }
}
