<?php

use Illuminate\Database\Seeder;

class NodesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Node::class, 100)->create();
        factory(App\NodeGroup::class, 50)->create();
        factory(App\NodeIPAddress::class, 600)->create();

        $system_data = [
            '{"CPU":{"Model":"Intel(R) Core(TM) i7-3770K CPU @ 3.50GHz","Cores":4,"Threads":8,"Cache Size":1024},"Memory":{"Physical":{"Used":16171319296,"Free":9536708608,"Total":25708027904},"Virtual":{"Used":27597258752,"Free":6432268288,"Total":34029527040}},"Environment":{"Hostname":"BLEU","OS Version":{"platform":2,"servicePack":"","version":{"major":6,"minor":2,"build":9200,"revision":0,"majorRevision":0,"minorRevision":0},"versionString":"Microsoft Windows NT 6.2.9200.0"},"64-Bits":true}}',
            '{"CPU":{"Model":" Intel(R) Xeon(R) CPU E3-1230 V2 @ 3.30GHz","Cores":4,"Threads":8,"Cache Size":" 8192 KB"},"Memory":{"Physical":{"Used":771387392,"Free":1376096256,"Total":2147483648}},"Environment":{"Hostname":"dev","OS Version":{"platform":4,"servicePack":"","version":{"major":2,"minor":6,"build":32,"revision":42,"majorRevision":0,"minorRevision":42},"versionString":"Unix 2.6.32.42"},"64-Bits":true}}'
        ];

        foreach (\App\Node::all() as $node)
        {
            $this->createServices($node);
            $node->system_data = array_random($system_data);
            $node->saveOrFail();
        }

        foreach (\App\Node::all()->random(40) as $node)
        {
            $node->group_id = null;
            $node->save();
        }

        foreach (\App\NodeGroup::all()->random(25) as $group)
        {
            $group->plan = \App\Constants\SubscriptionPlan::PRO; // TODO: Clean this up in production.
            $group->save();
        }

        foreach (\App\NodeGroup::all() as $group)
        {
            foreach ($group->nodes as $node)
            {
                if ($group->user_id != $node->user_id)
                {
                    $node->user_id = $group->user_id;
                    $node->saveOrFail();
                }
            }
        }

        // Let's add the only real nodes
        $this->seedRealNodes();


    }

    private function seedRealNodes ()
    {
        $realNode = new \App\Node();
        $realNode->ip = '23.158.64.30';
        $realNode->port = 6024;
        $realNode->friendly_name = 'Real Test Node 1';
        $realNode->protocol = 'http';
        $realNode->access_token = 'spectero:P1a1-5o--e2Ap-6S';
        $realNode->install_id = '23d0a0c4-dc91-4960-b6fc-2d874fb9f50f';
        $realNode->status = \App\Constants\NodeStatus::CONFIRMED;
        $realNode->market_model = \App\Constants\NodeMarketModel::LISTED_SHARED;
        $realNode->user_id = 6;
        $realNode->price = 15.99;
        $realNode->asn = 133535;
        $realNode->city = 'Seattle';
        $realNode->cc = 'US';
        $realNode->system_data = '{"CPU":{"Model":"Intel(R) Xeon(R) CPU E3-1230 V2 @ 3.30GHz","Cores":4,"Threads":40,"Cache Size":"8192 KB"},"Memory":{"Physical":{"Used":222289920,"Free":851451904,"Total":1073741824}},"Environment":{"Hostname":"daemon-test-0","OS Version":{"Platform":4,"ServicePack":"","Version":{"Major":2,"Minor":6,"Build":32,"Revision":42,"MajorRevision":0,"MinorRevision":42},"VersionString":"Unix 2.6.32.42"},"64-Bits":true}}';
        $realNode->app_settings = "{}";
        $realNode->system_config = "{}";
        $realNode->save();

        $this->createServices($realNode);

        $testNodeZero = new \App\Node();
        $testNodeZero->id = 102;
        $testNodeZero->ip = '23.172.128.21';
        $testNodeZero->port = 6024;
        $testNodeZero->protocol = 'http';
        $testNodeZero->friendly_name = "Real Test Node 2 (test-daemon-0)";
        $testNodeZero->access_token = 'cloud:yHZz7E_oE0_A_911-j2kK-_V';
        $testNodeZero->install_id = 'f1f5883a-d4bd-452c-b37e-f3c386685c4c';
        $testNodeZero->status = \App\Constants\NodeStatus::CONFIRMED;
        $testNodeZero->market_model = \App\Constants\NodeMarketModel::UNLISTED;
        $testNodeZero->user_id = 8;
        $testNodeZero->price = 13.20;
        $testNodeZero->asn = 46686;
        $testNodeZero->city = 'Seattle';
        $testNodeZero->cc = 'US';
        $testNodeZero->system_data = '{"CPU":{"Model":"Intel(R) Xeon(R) CPU E3-1230 V2 @ 3.30GHz","Cores":4,"Threads":40,"Cache Size":"8192 KB"},"Memory":{"Physical":{"Used":222289920,"Free":851451904,"Total":1073741824}},"Environment":{"Hostname":"daemon-test-0","OS Version":{"Platform":4,"ServicePack":"","Version":{"Major":2,"Minor":6,"Build":32,"Revision":42,"MajorRevision":0,"MinorRevision":42},"VersionString":"Unix 2.6.32.42"},"64-Bits":true}}';
        $testNodeZero->app_settings = '{}';
        $testNodeZero->system_config = '{}';
        $testNodeZero->save();

        $testServiceZero = new \App\Service();
        $testServiceZero->node_id = 102;
        $testServiceZero->type = \App\Constants\ServiceType::HTTPProxy;
        $testServiceZero->config = '[{"listeners":[{"item1":"23.172.128.21","item2":10240}],"allowedDomains":null,"bannedDomains":null,"proxyMode":"Normal"}]';
        $testServiceZero->connection_resource = '{"accessReference":["23.172.128.21:10240"],"accessConfig":null,"accessCredentials":"SPECTERO_USERNAME_PASSWORD"}';
        $testServiceZero->save();

        $testNodeOne = new \App\Node();
        $testNodeOne->id = 103;
        $testNodeOne->ip = '23.172.128.25';
        $testNodeOne->port = 6024;
        $testNodeOne->protocol = 'http';
        $testNodeOne->friendly_name = "Real Test Node 3 (test-daemon-1)";
        $testNodeOne->access_token = 'cloud:VX0-8xWpZ_2_7F5E9a_n_LLR';
        $testNodeOne->install_id = '0a3d3d2e-3e7b-46ba-a7b6-3a0b4db82f75';
        $testNodeOne->status = \App\Constants\NodeStatus::CONFIRMED;
        $testNodeOne->market_model = \App\Constants\NodeMarketModel::UNLISTED;
        $testNodeOne->user_id = 8;
        $testNodeOne->price = 13.20;
        $testNodeOne->asn = 46686;
        $testNodeOne->city = 'Seattle';
        $testNodeOne->cc = 'US';
        $testNodeOne->system_data = '{"CPU":{"Model":"Intel(R) Xeon(R) CPU E3-1230 V2 @ 3.30GHz","Cores":4,"Threads":40,"Cache Size":"8192 KB"},"Memory":{"Physical":{"Used":222289920,"Free":851451904,"Total":1073741824}},"Environment":{"Hostname":"daemon-test-0","OS Version":{"Platform":4,"ServicePack":"","Version":{"Major":2,"Minor":6,"Build":32,"Revision":42,"MajorRevision":0,"MinorRevision":42},"VersionString":"Unix 2.6.32.42"},"64-Bits":true}}';
        $testNodeOne->app_settings = '{}';
        $testNodeOne->system_config = '{}';
        $testNodeOne->save();

        $testServiceOne = new \App\Service();
        $testServiceOne->node_id = 103;
        $testServiceOne->type = \App\Constants\ServiceType::HTTPProxy;
        $testServiceOne->config = '[{"listeners":[{"item1":"23.172.128.25","item2":10240}],"allowedDomains":null,"bannedDomains":null,"proxyMode":"Normal"}]';
        $testServiceOne->connection_resource = '{"accessReference":["23.172.128.25:10240"],"accessConfig":null,"accessCredentials":"SPECTERO_USERNAME_PASSWORD"}';
        $testServiceOne->save();
    }

    private function createServices(\App\Node $node)
    {
        foreach (\App\Constants\ServiceType::getConstants() as $type)
        {
            $service = new \App\Service();
            $service->node_id = $node->id;
            $service->type = $type;
            $service->config = json_encode([
                                               'DatabaseFile' => 'Database/db.sqlite',
                                               "PasswordCostTimeThreshold" => 100.0,
                                               "SpaCacheTime" => 1,
                                           ]);

            $randStr = null;
            $ref = null;
            if ($type != \App\Constants\ServiceType::HTTPProxy)
            {
                $ref = $this->generateAccessReferences(2);
                $randStr = \App\Libraries\Utility::getRandomString(2);
                for ($i = 0; $i <= 5; $i++)
                    $randStr .= PHP_EOL . $randStr;
            }
            else
                $ref = $this->generateAccessReferences(mt_rand(5, 10));

            $service->connection_resource = json_encode([
                                                            'accessReference' => [
                                                                $ref
                                                            ],
                                                            'accessConfig' => $randStr,
                                                            'accessCredentials' => array_random(['SPECTERO_USERNAME_PASSWORD', $node->access_token])
                                                        ]);

            $service->saveOrFail();
        }
    }

    private function generateAccessReferences(int $bound = 5) : array
    {
        $out = [];

        while ($bound)
        {
            $ipSeed = mt_rand(1, 63);
            $ip = $ipSeed . '.' . $ipSeed * 2 . '.' . $ipSeed * 3 . '.' . $ipSeed * 4 . ':' . mt_rand(10240, 65534);

            $out[] = $ip;

            $bound--;
        }

        return $out;
    }
}
