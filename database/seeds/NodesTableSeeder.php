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
        factory(App\Node::class, 15)->create();
        factory(App\NodeGroup::class, 5)->create();

        foreach (range(1, 5) as $index)
        {
            \App\Node::create([
                'ip' => '242.133.252.12'. $index,
                'port' => 8080,
                'protocol' => 'HTTP',
                'access_token' => 'cloudUser' . ':' . \App\Libraries\Utility::getRandomString(),
                'install_id' => \App\Libraries\Utility::getRandomString(),
                'status' => array_random(\App\Constants\NodeStatus::getConstants()),
                'user_id' => 6,
                'price' => 9.99,
                'market_model' => array_random(\App\Constants\NodeMarketModel::getConstants()),
                'group_id' => null
            ]);
        }
    }
}
