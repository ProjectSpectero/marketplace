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
        factory(App\NodeGroup::class, 25)->create();

        foreach (\App\Node::all()->random(40) as $node)
        {
            $node->group_id = null;
            $node->save();
        }

        foreach (\App\NodeGroup::all() as $group)
        {
            foreach ($group->nodes as $node)
            {
                $this->createServices($node);
                if ($group->user_id != $node->user_id)
                {
                    $node->user_id = $group->user_id;
                    $node->saveOrFail();
                }
            }
        }
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
            $service->config = json_encode($node->friendly_name);
            $service->connection_resource = json_encode($node->ip);

            $service->saveOrFail();

            $this->createNodeIPs($service, $node);
        }

    }

    private function createNodeIPs(\App\Service $service, \App\Node$node)
    {
        $ip = new \App\NodeIPAddress();
        $ip->ip = $node->ip;
        $ip->node_id = $node->id;

        $ip->saveOrFail();
    }
}
