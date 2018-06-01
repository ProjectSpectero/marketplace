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

            while($items)
            {
                $amount = mt_rand(1, 100);
                $qtyEach = mt_rand(1, 5);

                $totalAmount += $amount * $qtyEach;

                $determinedType = array_random(\App\Constants\OrderResourceType::getConstants());
                if ($determinedType == \App\Constants\OrderResourceType::NODE_GROUP)
                    $resourceId = mt_rand(1, 50);
                else
                    $resourceId = mt_rand(1, 100);

                $lineItem = new \App\OrderLineItem();
                $lineItem->description = "Example line item";
                $lineItem->order_id = $order->id;
                $lineItem->type = $determinedType;
                $lineItem->resource = $resourceId;
                $lineItem->quantity = $qtyEach;
                $lineItem->amount = $amount;
                $lineItem->status = array_random([ \App\Constants\OrderStatus::ACTIVE, \App\Constants\OrderStatus::PENDING ]);
                $lineItem->sync_status = array_random(\App\Constants\NodeSyncStatus::getConstants());
                $lineItem->sync_timestamp = $timestamp;
                $lineItem->saveOrFail();

                $items--;
            }

            if ($order->status == \App\Constants\OrderStatus::ACTIVE)
                $invoiceStatus = \App\Constants\InvoiceStatus::PAID;
            elseif ($order->status == \App\Constants\OrderStatus::CANCELLED)
                $invoiceStatus = \App\Constants\InvoiceStatus::CANCELLED;
            else
                $invoiceStatus = \App\Constants\InvoiceStatus::UNPAID;

            $invoice = new \App\Invoice();
            $invoice->id = mt_rand(1, 100000);
            $invoice->order_id = $order->id;
            $invoice->user_id = $order->user_id;
            $invoice->amount = $totalAmount;
            $invoice->currency = \App\Constants\Currency::USD;
            $invoice->status = $invoiceStatus;
            $invoice->due_date = \Carbon\Carbon::now();
            $invoice->type = \App\Constants\InvoiceType::STANDARD;
            $invoice->last_reminder_sent = \Carbon\Carbon::now()->subDay(mt_rand(1, 15));
            $invoice->saveOrFail();

            $order->last_invoice_id = $invoice->id;
            $order->saveOrFail();
        }
    }
}
