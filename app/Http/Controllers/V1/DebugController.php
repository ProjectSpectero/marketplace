<?php

namespace App\Http\Controllers\V1;
use App\Libraries\NodeManager;
use App\Node;
use Illuminate\Http\Request;

class DebugController
{
    public function test (Request $request)
    {
        $node = Node::find(8);
        $manager = new NodeManager($node);
        dd($manager);
    }
}