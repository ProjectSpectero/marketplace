<?php

namespace App\Http\Controllers\V1;
use App\Constants\Events;
use App\Events\NodeEvent;
use App\Libraries\NodeManager;
use App\Node;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DebugController
{
    public function test (Request $request)
    {
        $node = Node::find(6);
        $manager = new NodeManager($node);
        dd($manager);
    }
}