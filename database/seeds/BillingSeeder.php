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
        $lineItem = new \App\OrderLineItem();
        $lineItem->description = "Example line item";
        $lineItem->order_id = 1;
        $lineItem->type = \App\Constants\OrderResourceType::NODE;
        $lineItem->resource = \App\Libraries\Utility::getRandomString();
        $lineItem->quantity = 1;
        $lineItem->amount = 6.92;
        $lineItem->saveOrFail();

        $lineItem = new \App\OrderLineItem();
        $lineItem->description = "Example line item";
        $lineItem->order_id = 1;
        $lineItem->type = \App\Constants\OrderResourceType::NODE;
        $lineItem->resource = \App\Libraries\Utility::getRandomString();
        $lineItem->quantity = 2;
        $lineItem->amount = 5.44;
        $lineItem->saveOrFail();

        $lineItem = new \App\OrderLineItem();
        $lineItem->description = "Example line item";
        $lineItem->order_id = 2;
        $lineItem->type = \App\Constants\OrderResourceType::NODE;
        $lineItem->resource = \App\Libraries\Utility::getRandomString();
        $lineItem->quantity = 1;
        $lineItem->amount = 6.20;
        $lineItem->saveOrFail();

        $lineItem = new \App\OrderLineItem();
        $lineItem->description = "Example line item";
        $lineItem->order_id = 3;
        $lineItem->type = \App\Constants\OrderResourceType::NODE;
        $lineItem->resource = \App\Libraries\Utility::getRandomString();
        $lineItem->quantity = 1;
        $lineItem->amount = 26.20;
        $lineItem->saveOrFail();

        $lineItem = new \App\OrderLineItem();
        $lineItem->description = "Example line item";
        $lineItem->order_id = 4;
        $lineItem->type = \App\Constants\OrderResourceType::NODE;
        $lineItem->resource = \App\Libraries\Utility::getRandomString();
        $lineItem->quantity = 1;
        $lineItem->amount = 9.60;
        $lineItem->saveOrFail();

        $lineItem = new \App\OrderLineItem();
        $lineItem->description = "Example line item";
        $lineItem->order_id = 4;
        $lineItem->type = \App\Constants\OrderResourceType::NODE;
        $lineItem->resource = \App\Libraries\Utility::getRandomString();
        $lineItem->quantity = 5;
        $lineItem->amount = 20.00;
        $lineItem->saveOrFail();

        $lineItem = new \App\OrderLineItem();
        $lineItem->description = "Example line item";
        $lineItem->order_id = 5;
        $lineItem->type = \App\Constants\OrderResourceType::NODE;
        $lineItem->resource = \App\Libraries\Utility::getRandomString();
        $lineItem->quantity = 1;
        $lineItem->amount = 10.00;
        $lineItem->saveOrFail();

        $invoice = new \App\Invoice();
        $invoice->id = mt_rand(1, 10000);
        $invoice->order_id = 1;
        $invoice->amount = 17.80;
        $invoice->currency = \App\Constants\Currency::USD;
        $invoice->status = \App\Constants\InvoiceStatus::UNPAID;
        $invoice->saveOrFail();

        $invoice = new \App\Invoice();
        $invoice->id = mt_rand(1, 10000);
        $invoice->order_id = 2;
        $invoice->amount = 6.20;
        $invoice->currency = \App\Constants\Currency::USD;
        $invoice->status = \App\Constants\InvoiceStatus::UNPAID;
        $invoice->saveOrFail();

        $invoice = new \App\Invoice();
        $invoice->id = mt_rand(1, 10000);
        $invoice->order_id = 3;
        $invoice->amount = 26.20;
        $invoice->currency = \App\Constants\Currency::USD;
        $invoice->status = \App\Constants\InvoiceStatus::UNPAID;
        $invoice->saveOrFail();

        $invoice = new \App\Invoice();
        $invoice->id = mt_rand(1, 10000);
        $invoice->order_id = 4;
        $invoice->amount = 109.60;
        $invoice->currency = \App\Constants\Currency::USD;
        $invoice->status = \App\Constants\InvoiceStatus::UNPAID;
        $invoice->saveOrFail();

        $invoice = new \App\Invoice();
        $invoice->id = mt_rand(1, 10000);
        $invoice->order_id = 5;
        $invoice->amount = 10.00;
        $invoice->currency = \App\Constants\Currency::USD;
        $invoice->status = \App\Constants\InvoiceStatus::UNPAID;
        $invoice->saveOrFail();

        factory(App\Order::class, 5)->create();

    }
}
