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
        $enterpriseNodes = \App\Node::where('market_model', \App\Constants\NodeMarketModel::ENTERPRISE)->get();

        factory(App\Order::class, 250)->create();

        $fraudCheckOrders = \App\Order::whereIn('status', [ \App\Constants\OrderStatus::AUTOMATED_FRAUD_CHECK, \App\Constants\OrderStatus::MANUAL_FRAUD_CHECK ]);
        $fraudCheckOrdersCount = $fraudCheckOrders->count();

        $fraudCheckOrders = $fraudCheckOrders->get()->random(floor($fraudCheckOrdersCount / 2));

        // CF-370: Skew the seeder to favor ACTIVE or PENDING instead of the FRAUD_CHECK types.
        /** @var \App\Order $order */
        foreach ($fraudCheckOrders as $order)
        {
            $order->status = array_random([ \App\Constants\OrderStatus::PENDING, \App\Constants\OrderStatus::ACTIVE ]);
            $order->saveOrFail();
        }

        /** @var \App\Order $order */
        foreach (App\Order::noEagerLoads()->get() as $order)
        {
            $timestamp = \Carbon\Carbon::now();
            $items = mt_rand(1, 10);

            $totalAmount = 0;

            while($items > 0)
            {
                $amount = mt_rand(1, 100);
                $qtyEach = mt_rand(1, 5);

                $iterationAmount = $amount * $qtyEach;
                $totalAmount += $iterationAmount;

                $determinedType = array_random(\App\Constants\OrderResourceType::getConstants());

                // To prevent it from occurring too often
                if ($determinedType == \App\Constants\OrderResourceType::ENTERPRISE)
                {
                    $seed = mt_rand(1, 10);
                    // I'm so random XD (!)
                    if ($seed % 2 == 0)
                        $determinedType = array_random(\App\Constants\OrderResourceType::getConstants());
                }


                if ($determinedType == \App\Constants\OrderResourceType::NODE_GROUP)
                    $resourceId = mt_rand(1, 50);
                elseif ($determinedType == \App\Constants\OrderResourceType::ENTERPRISE)
                {
                    $resourceId = mt_rand(1, 1000);
                    // The loop needs to stop after this iteration, ent orders are always solo.
                    $items = 0;
                    // To enforce ^, let's get rid of all its existing lineitems.
                    $order->lineItems()->delete();
                    // Let's fix the total-amount too.
                    $totalAmount = $iterationAmount;
                }
                else
                    $resourceId = mt_rand(1, 1000);

                $res = null;

                switch ($determinedType)
                {
                    case \App\Constants\OrderResourceType::NODE:
                        $res = \App\Node::find($resourceId);
                        break;

                    case \App\Constants\OrderResourceType::NODE_GROUP:
                        $res = \App\NodeGroup::find($determinedType);
                        break;
                }

                if ($res != null && ! $res->isMarketable())
                {
                    $res->market_model = array_random(\App\Constants\NodeMarketModel::getMarketable());
                    $res->saveOrFail();
                }

                $lineItem = new \App\OrderLineItem();
                $lineItem->description = "Bananas (this the plan yo)";
                $lineItem->order_id = $order->id;
                $lineItem->type = $determinedType;
                $lineItem->resource = $resourceId;
                $lineItem->quantity = $qtyEach;
                $lineItem->amount = $amount;
                $lineItem->status = array_random([ \App\Constants\OrderStatus::ACTIVE, \App\Constants\OrderStatus::PENDING ]);
                $lineItem->sync_status = array_random(\App\Constants\NodeSyncStatus::getConstants());
                $lineItem->sync_timestamp = $timestamp;
                $lineItem->saveOrFail();

                if ($determinedType == \App\Constants\OrderResourceType::ENTERPRISE)
                {
                    // Let's seed some IPs / such things too.
                    /** @var \App\Node $hostingNode */
                    $hostingNode = $enterpriseNodes->random();
                    $hostingIPs = $hostingNode->ipAddresses();
                    $hostingIPCount = $hostingIPs->count();

                    if ($hostingIPCount == 0)
                    {
                        $lineItem->delete();
                        continue;
                    }

                    $resourcesToCreate = mt_rand(1, $hostingIPCount);

                    /** @var \Illuminate\Database\Eloquent\Collection $chosenIPs */
                    $chosenIPs = $hostingIPs->get()->random($resourcesToCreate)->toArray();

                    while ($resourcesToCreate > 0)
                    {
                        $tmpIp = $chosenIPs[$resourcesToCreate - 1];

                        $enterpriseResource = new \App\EnterpriseResource();

                        $enterpriseResource->ip_id = $tmpIp['id'];
                        $enterpriseResource->outgoing_ip_id = $tmpIp['id'];

                        $enterpriseResource->port = 10240;
                        $enterpriseResource->order_line_item_id = $lineItem->id;

                        $enterpriseResource->saveOrFail();

                        array_pop($chosenIPs);

                        $resourcesToCreate--;
                    }
                }

                $items--;
            }

            if ($order->status == \App\Constants\OrderStatus::ACTIVE)
                $invoiceStatus = \App\Constants\InvoiceStatus::PAID;
            elseif ($order->status == \App\Constants\OrderStatus::CANCELLED)
                $invoiceStatus = \App\Constants\InvoiceStatus::CANCELLED;
            else
                $invoiceStatus = \App\Constants\InvoiceStatus::UNPAID;

            $invoice = new \App\Invoice();
            $invoice->id = mt_rand(1, 1000000);
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
