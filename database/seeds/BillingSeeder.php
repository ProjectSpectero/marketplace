<?php

use Illuminate\Database\Seeder;

class BillingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Order::class, 100)->create();
        foreach (App\Order::noEagerLoads()->get() as $order)
        {
            $timestamp = \Carbon\Carbon::now();
            $items = mt_rand(1, 10);

            $totalAmount = 0;

            for ($i = $items; $i <= $items; $i++)
            {
                $amount = mt_rand(1, 100);
                $qtyEach = mt_rand(1, 5);

                $totalAmount += $amount * $qtyEach;

                $lineItem = new \App\OrderLineItem();
                $lineItem->description = "Example line item $i";
                $lineItem->order_id = $order->id;
                $lineItem->type = array_rand(\App\Constants\OrderResourceType::getConstants());
                $lineItem->resource = mt_rand(1, 100);
                $lineItem->quantity = $qtyEach;
                $lineItem->amount = $amount;
                $lineItem->status = array_rand([ \App\Constants\OrderStatus::ACTIVE, \App\Constants\OrderStatus::PENDING ]);
                $lineItem->sync_status = array_rand(\App\Constants\NodeSyncStatus::getConstants());
                $lineItem->sync_timestamp = $timestamp;
                $lineItem->saveOrFail();
            }

            $invoice = new \App\Invoice();
            $invoice->id = mt_rand(1, 100000);
            $invoice->order_id = $order->id;
            $invoice->user_id = $order->user_id;
            $invoice->amount = $totalAmount;
            $invoice->currency = \App\Constants\Currency::USD;
            $invoice->status = \App\Constants\InvoiceStatus::UNPAID;
            $invoice->due_date = \Carbon\Carbon::now();
            $invoice->type = \App\Constants\InvoiceType::STANDARD;
            $invoice->last_reminder_sent = \Carbon\Carbon::now()->subDay(mt_rand(1, 15));
            $invoice->saveOrFail();

            $order->last_invoice_id = $invoice->id;
            $order->saveOrFail();
        }
    }
}
