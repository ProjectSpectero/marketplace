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
        factory(App\NodeIPAddress::class, 400)->create();

        foreach (\App\Node::all() as $node)
        {
            $this->createServices($node);
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

        // Let's add the only real node, eh?

        $realNode = new \App\Node();
        $realNode->ip = '23.172.128.100';
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
        $realNode->loaded_config = "";
        $realNode->save();

        $this->createServices($realNode);
    }

    private function createServices(\App\Node $node)
    {
        for ($i = 0; $i < 4; $i++)
        {
            if ($i == 0)
                $type = \App\Constants\ServiceType::HTTPProxy;
            elseif ($i == 1)
                $type = \App\Constants\ServiceType::OpenVPN;
            elseif ($i == 2)
                $type = \App\Constants\ServiceType::ShadowSOCKS;
            else
                $type = \App\Constants\ServiceType::SSHTunnel;

            $service = new \App\Service();
            $service->node_id = $node->id;
            $service->type = $type;
            $service->config = json_encode([
                'DatabaseFile' => 'Database/db.sqlite',
                "PasswordCostTimeThreshold" => 100.0,
                "SpaCacheTime" => 1,
            ]);

            $randStr = \App\Libraries\Utility::getRandomString(10);
            for ($i = 0; $i <= 5; $i++)
                $randStr .= PHP_EOL . $randStr;

            $ipSeed = mt_rand(1, 63);
            $ip = $ipSeed . '.' . $ipSeed * 2 . '.' . $ipSeed * 3 . '.' . $ipSeed * 4 . ':' . mt_rand(10240, 65534);

            $service->connection_resource = json_encode([
                'accessReference' => [
                    $ip
                ],
                'accessConfig' => $randStr,
                'accessCredentials' => array_random(['SPECTERO_USERNAME_PASSWORD', $node->access_token])
            ]);

            $service->saveOrFail();
        }

    }
}
