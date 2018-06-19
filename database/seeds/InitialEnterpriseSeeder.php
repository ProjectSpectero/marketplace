<?php

use App\Constants\UserMetaKeys;
use App\Constants\UserRoles;
use App\Libraries\PermissionManager;
use App\UserMeta;
use Illuminate\Database\Seeder;

class InitialEnterpriseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->stageUsers();
        $this->stageBilling();
        $this->stageEnterpriseNodes();
    }

    private function stageEnterpriseNodes ()
    {
        $internalUser = \App\User::where('email', '=', 'noc@spectero.com')
            ->first();

        $dallasOne = new \App\Node();
        $dallasTwo = new App\Node();

        $allNodes = [ $dallasOne, $dallasTwo ];

        $dallasOne->id = 1001;
        $dallasTwo->id = 1002;

        $dallasOne->ip = '108.61.204.122';
        $dallasTwo->ip = '207.148.4.117';

        $dallasOne->friendly_name = 'Enterprise (Dallas) #1';
        $dallasTwo->friendly_name = 'Enterprise (Dallas) #2';

        $dallasOne->access_token = 'cloud:gTqu85zK3NiaAm9QpHfLT7MV';
        $dallasTwo->access_token ='cloud:chMS6s0W2ot7kuPyfwAQyHn5F';

        $dallasOne->install_id = 'de1dc2bc-585f-479e-812c-5ebe8b9cd76c';
        $dallasTwo->install_id = 'eb3d50eb-4b74-4463-968d-5c60db0726c2';

        $this->apply($allNodes, 'port', 6024);
        $this->apply($allNodes, 'protocol', 'http');
        $this->apply($allNodes, 'status', \App\Constants\NodeStatus::CONFIRMED);
        $this->apply($allNodes, 'market_model', \App\Constants\NodeMarketModel::ENTERPRISE);
        $this->apply($allNodes, 'user_id', $internalUser->id);
        $this->apply($allNodes, 'asn', 31894);
        $this->apply($allNodes, 'city', 'Dallas');
        $this->apply($allNodes, 'cc', 'US');
        $this->apply($allNodes, 'version', 'v0.1-alpha');
        $this->apply($allNodes, 'system_data',
                     '{"CPU":{"Model":"Intel(R) Xeon(R) CPU E3-1230 V2 @ 3.30GHz","Cores":4,"Threads":40,"Cache Size":"8192 KB"},"Memory":{"Physical":{"Used":222289920,"Free":851451904,"Total":1073741824}},"Environment":{"Hostname":"daemon-test-0","OS Version":{"Platform":4,"ServicePack":"","Version":{"Major":2,"Minor":6,"Build":32,"Revision":42,"MajorRevision":0,"MinorRevision":42},"VersionString":"Unix 2.6.32.42"},"64-Bits":true}}'
        );
        $this->apply($allNodes, 'app_settings', '{}');
        $this->apply($allNodes, 'system_config', '{}');

        foreach ($allNodes as $node)
            $node->save();

        // Let's add the IP addresses, no support for outgoing IP tracking at the moment.
        $dallasOneAggregatorIP = new \App\NodeIPAddress();
        $dallasTwoAggregatorIP = new \App\NodeIPAddress();

        $allIPs = [ $dallasOneAggregatorIP, $dallasTwoAggregatorIP ];

        $dallasOneAggregatorIP->ip = '23.155.192.2';
        $dallasOneAggregatorIP->node_id = 1001;

        $dallasTwoAggregatorIP->ip = '23.155.192.3';
        $dallasTwoAggregatorIP->node_id = 1002;

        $this->apply($allIPs, 'city', 'Dallas');
        $this->apply($allIPs, 'cc', 'US');
        $this->apply($allIPs, 'asn', 31894);

        foreach ($allIPs as $ip)
            $ip->save();

        // CPH -> blue resources
        for ($i = 12500; $i < 15501; $i++)
        {
            foreach ($allIPs as $ip)
            {
                $resource = new \App\EnterpriseResource();
                $resource->ip_id = $ip->id;
                $resource->port = $i;

                $resource->order_line_item_id = 1001;

                $resource->saveOrFail();
            }
        }

        // Green resources, commented out by default
        /*
            for ($i = 32500; $i < 32601; $i++)
            {
                $resource = new \App\EnterpriseResource();
                $resource->ip_id = $dallasOneAggregatorIP->id;
                $resource->port = $i;

                $resource->order_line_item_id = 1001;

                $resource->saveOrFail();
            }
        */

        // Yellow Resources, Jesse.
        for ($i = 40150; $i < 40211; $i++)
        {
            $resource = new \App\EnterpriseResource();
            $resource->ip_id = $dallasTwoAggregatorIP->id;
            $resource->port = $i;

            $resource->order_line_item_id = 1002;

            $resource->saveOrFail();
        }

    }

    private function apply (array $nodes, string $propertyName, $value)
    {
        foreach ($nodes as $node)
        {
            $node->{$propertyName} = $value;
        }
    }

    private function stageBilling ()
    {
        $cahpyon = \App\User::where('email', '=', 'stefan.matei@caphyon.com')->firstOrFail();
        $rankingPress = \App\User::where('email', '=', 'jesse@rankingpress.com')->firstOrFail();

        $cahpyonOrder = new \App\Order();
        $rankingPressOrder = new \App\Order();

        $orders = [ $cahpyonOrder, $rankingPressOrder ];

        $cahpyonOrder->id = 1001;
        $rankingPressOrder->id = 1002;

        $cahpyonOrder->user_id = $cahpyon->id;
        $rankingPressOrder->user_id = $rankingPress->id;

        $cahpyonOrder->accessor = 'blue:sQUqqGKgkwKY8JFk';
        $rankingPressOrder->accessor = 'green:MPp8NhuZ8aHjajaE';

        $this->apply($orders, 'status', \App\Constants\OrderStatus::ACTIVE);
        $this->apply($orders, 'term', 30);
        $this->apply($orders, 'due_next', '2018-07-01');

        foreach ($orders as $order)
            $order->save();

        $cahpyonLineItem = new \App\OrderLineItem();
        $rankingPressLineItem = new \App\OrderLineItem();

        $cahpyonLineItem->id = 1001;
        $rankingPressLineItem->id = 1002;

        $lineItems = [ $cahpyonLineItem, $rankingPressLineItem ];

        $this->apply($lineItems, 'description', 'Spectero: Enterprise Proxies');
        $this->apply($lineItems, 'type', \App\Constants\OrderResourceType::ENTERPRISE);
        $this->apply($lineItems, 'status', \App\Constants\OrderStatus::ACTIVE);
        $this->apply($lineItems, 'resource', array_random([ 1001, 1002 ]));
        $this->apply($lineItems, 'sync_status', \App\Constants\NodeSyncStatus::SYNCED);
        $this->apply($lineItems, 'sync_timestamp', \Carbon\Carbon::now());

        $cahpyonLineItem->order_id = 1001;
        $rankingPressLineItem->order_id = 1002;

        $cahpyonLineItem->amount = 1.8;
        $cahpyonLineItem->quantity = 6000;

        $rankingPressLineItem->amount = 1;
        $rankingPressLineItem->quantity = 60;

        foreach ($lineItems as $lineItem)
            $lineItem->save();

        // Now, we need to populate the older transactions and invoices.
        $invoices = [
            [
                'id' => 1001, 'order_id' => 1001, 'user_id' => $cahpyon->id, 'amount' => 10800, 'tax' => 0, 'currency' => \App\Constants\Currency::USD, 'type' => \App\Constants\InvoiceType::STANDARD,
                'status' => \App\Constants\InvoiceStatus::PAID, 'due_date' => '2018-03-01', 'transaction' => [ 'processor' => \App\Constants\PaymentProcessor::MANUAL, 'fee' => 45 ]
            ],
            [
                'id' => 1002, 'order_id' => 1002, 'user_id' => $rankingPress->id, 'amount' => 60, 'tax' => 0, 'currency' => \App\Constants\Currency::USD, 'type' => \App\Constants\InvoiceType::STANDARD,
                'status' => \App\Constants\InvoiceStatus::PAID, 'due_date' => '2018-03-01', 'transaction' => [ 'processor' => \App\Constants\PaymentProcessor::STRIPE, 'fee' => 2.64, 'reference' => 'ch_1C5YszDU5jz0ryLFxQx0BwJp' ]
            ],
            [
                'id' => 1004, 'order_id' => 1001, 'user_id' => $cahpyon->id, 'amount' => 10800, 'tax' => 0, 'currency' => \App\Constants\Currency::USD, 'type' => \App\Constants\InvoiceType::STANDARD,
                'status' => \App\Constants\InvoiceStatus::PAID, 'due_date' => '2018-04-01', 'transaction' => [ 'processor' => \App\Constants\PaymentProcessor::MANUAL, 'fee' => 45 ]
            ],
            [
                'id' => 1005, 'order_id' => 1002, 'user_id' => $rankingPress->id, 'amount' => 60, 'tax' => 0, 'currency' => \App\Constants\Currency::USD, 'type' => \App\Constants\InvoiceType::STANDARD,
                'status' => \App\Constants\InvoiceStatus::PAID, 'due_date' => '2018-04-01', 'transaction' => [ 'processor' => \App\Constants\PaymentProcessor::STRIPE, 'fee' => 2.64, 'reference' => 'ch_1C5YwvDU5jz0ryLFV9rhF0a9' ]
            ],
            [
                'id' => 1006, 'order_id' => 1001, 'user_id' => $cahpyon->id, 'amount' => 10800, 'tax' => 0, 'currency' => \App\Constants\Currency::USD, 'type' => \App\Constants\InvoiceType::STANDARD,
                'status' => \App\Constants\InvoiceStatus::PAID, 'due_date' => '2018-05-01', 'transaction' => [ 'processor' => \App\Constants\PaymentProcessor::MANUAL, 'fee' => 45 ]
            ],
            [
                'id' => 1008, 'order_id' => 1002, 'user_id' => $rankingPress->id, 'amount' => 60, 'tax' => 0, 'currency' => \App\Constants\Currency::USD, 'type' => \App\Constants\InvoiceType::STANDARD,
                'status' => \App\Constants\InvoiceStatus::PAID, 'due_date' => '2018-05-01', 'transaction' => [ 'processor' => \App\Constants\PaymentProcessor::STRIPE, 'fee' => 2.64, 'reference' => 'ch_1CILaMDU5jz0ryLFC4YbhkSd' ]
            ],
            [
                'id' => 1009, 'order_id' => 1001, 'user_id' => $cahpyon->id, 'amount' => 10800, 'tax' => 0, 'currency' => \App\Constants\Currency::USD, 'type' => \App\Constants\InvoiceType::STANDARD,
                'status' => \App\Constants\InvoiceStatus::PAID, 'due_date' => '2018-06-01', 'transaction' => [ 'processor' => \App\Constants\PaymentProcessor::MANUAL, 'fee' => 45 ]
            ],
            [
                'id' => 1010, 'order_id' => 1002, 'user_id' => $rankingPress->id, 'amount' => 60, 'tax' => 0, 'currency' => \App\Constants\Currency::USD, 'type' => \App\Constants\InvoiceType::STANDARD,
                'status' => \App\Constants\InvoiceStatus::PAID, 'due_date' => '2018-06-01', 'transaction' => [ 'processor' => \App\Constants\PaymentProcessor::STRIPE, 'fee' => 2.64, 'reference' => 'ch_1CTz5PDU5jz0ryLF5albWbvj' ]
            ],
        ];

        foreach ($invoices as $data)
        {
            $invoice = new \App\Invoice();
            $transactionDetails = $data['transaction'];
            unset($data['transaction']);

            $processor = null;
            $notes = '';
            $reference = '';

            switch ($transactionDetails['processor'])
            {
                case \App\Constants\PaymentProcessor::STRIPE:
                    $processor = new \App\Libraries\Payment\StripeProcessor(new \Illuminate\Http\Request());
                    $reference = $transactionDetails['reference'];
                    break;

                default:
                    $processor = new \App\Libraries\Payment\ManualPaymentProcessor(new \Illuminate\Http\Request());
                    $notes = json_encode([
                                             'authorized_by' => 'Internal System (Auto auth)',
                                             'authorized_from' => '127.0.0.1',
                                             'note' => "Interbank/WIRE into Spectero Chase Account"
                                         ]);
                    $reference = \App\Libraries\Utility::getRandomString(2);
                    break;
            }

            foreach ($data as $key => $value)
            {
                $invoice->{$key} = $value;
            }
            $invoice->saveOrFail();

            \App\Libraries\BillingUtils::addTransaction($processor, $invoice, $invoice->amount, $transactionDetails['fee'],
                                                        $reference, \App\Constants\PaymentType::CREDIT, \App\Constants\TransactionReasons::PAYMENT, $notes);
        }


    }

    private function stageUsers ()
    {
        // Two enterprise users, currently.
        $cahpyon = \App\User::create([
                                       'name' => 'Stefan Matei',
                                       'email' => 'stefan.matei@caphyon.com',
                                       'password' => '$2y$10$6L7kpXcU/7XBQtM3MQIaMOlcD1fYjOKrkKaPlOQlu1r/Sx3amPKCq',
                                       'status' => \App\Constants\UserStatus::ACTIVE,
                                       'node_key' => \App\Libraries\Utility::getRandomString(2)
                                   ]);

        PermissionManager::assign($cahpyon, UserRoles::USER);

        UserMeta::addOrUpdateMeta($cahpyon, UserMetaKeys::AddressLineOne, 'Str. Ana Ipatescu Nr. 51');
        UserMeta::addOrUpdateMeta($cahpyon, UserMetaKeys::Organization, 'SC Caphyon SRL');
        UserMeta::addOrUpdateMeta($cahpyon, UserMetaKeys::PreferredCurrency, 'USD');
        UserMeta::addOrUpdateMeta($cahpyon, UserMetaKeys::City, 'Craiova');
        UserMeta::addOrUpdateMeta($cahpyon, UserMetaKeys::State, 'Dolj');
        UserMeta::addOrUpdateMeta($cahpyon, UserMetaKeys::Country, 'RO');
        UserMeta::addOrUpdateMeta($cahpyon, UserMetaKeys::PostCode, '077190');

        $rankingpress = \App\User::create([
                                              'name' => 'Jesse Hanley',
                                              'email' => 'jesse@rankingpress.com',
                                              'password' => '$2y$10$ydINU.wfEBDvS4U9e1nN.u3HAjKIF18WKacxjiRm4szL7RXo/ZHae',
                                              'status' => \App\Constants\UserStatus::ACTIVE,
                                              'node_key' => \App\Libraries\Utility::getRandomString(2)
                                          ]);

        PermissionManager::assign($rankingpress, UserRoles::USER);

        UserMeta::addOrUpdateMeta($rankingpress, UserMetaKeys::AddressLineOne, '27 Glover Street');
        UserMeta::addOrUpdateMeta($rankingpress, UserMetaKeys::Organization, 'Rankingpress');
        UserMeta::addOrUpdateMeta($rankingpress, UserMetaKeys::PreferredCurrency, 'USD');
        UserMeta::addOrUpdateMeta($rankingpress, UserMetaKeys::City, 'Sydney');
        UserMeta::addOrUpdateMeta($rankingpress, UserMetaKeys::State, 'New South Wales');
        UserMeta::addOrUpdateMeta($rankingpress, UserMetaKeys::Country, 'AU');
        UserMeta::addOrUpdateMeta($rankingpress, UserMetaKeys::PostCode, '2068');

        $internalUser = \App\User::create([
                                              'name' => 'Spectero Team',
                                              'email' => 'noc@spectero.com',
                                              'password' => '$2y$10$nI9AbpL0ytb7MpdxH7S.dOXwDFPskbCLBzRsEYEMRDYIsKahq2Q/K',
                                              'status' => \App\Constants\UserStatus::ACTIVE,
                                              'node_key' => '37e2f8ee15e3519b145de04676a11cae9eff0dafe7f874769612808673a349c9'
                                          ]);

        PermissionManager::assign($internalUser, UserRoles::USER);
    }
}
