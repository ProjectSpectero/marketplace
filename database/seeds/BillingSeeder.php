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
        factory(App\Order::class, 5)->create();
        foreach (\App\Order::all() as $order)
        {
            $amountOne = mt_rand(1, 100);
            $amountTwo = mt_rand(1, 100);
            $quantity = mt_rand(1, 5);

            $lineItem = new \App\OrderLineItem();
            $lineItem->description = "Example line item 1";
            $lineItem->order_id = $order->id;
            $lineItem->type = \App\Constants\OrderResourceType::NODE;
            $lineItem->resource = \App\Libraries\Utility::getRandomString();
            $lineItem->quantity = $quantity;
            $lineItem->amount = $amountOne;
            $lineItem->saveOrFail();

            $lineItem = new \App\OrderLineItem();
            $lineItem->description = "Example line item 2";
            $lineItem->order_id = $order->id;
            $lineItem->type = \App\Constants\OrderResourceType::NODE;
            $lineItem->resource = \App\Libraries\Utility::getRandomString();
            $lineItem->quantity = $quantity;
            $lineItem->amount = $amountTwo;
            $lineItem->saveOrFail();

            $invoice = new \App\Invoice();
            $invoice->id = mt_rand(1, 10000);
            $invoice->order_id = $order->id;
            $invoice->user_id = 6;
            $invoice->amount = ($amountOne + $amountTwo) * $quantity;
            $invoice->currency = \App\Constants\Currency::USD;
            $invoice->status = \App\Constants\InvoiceStatus::UNPAID;
            $invoice->saveOrFail();

            $order->last_invoice_id = $invoice->id;
            $order->saveOrFail();
        }
    }
}
