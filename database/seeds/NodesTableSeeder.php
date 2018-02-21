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
        factory(App\Node::class, 25)->create();
        factory(App\NodeGroup::class, 5)->create();

        foreach (\App\Node::all()->random(10) as $node)
        {
            $node->group_id = null;
            $node->save();
        }
    }
}
