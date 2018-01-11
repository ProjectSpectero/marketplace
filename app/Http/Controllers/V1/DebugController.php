<?php

namespace App\Http\Controllers\V1;
use App\Constants\Events;
use App\Events\NodeEvent;
use App\Node;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DebugController
{
    public function helloWorld (Request $request)
    {
        Cache::put("t.val", new NodeEvent(Events::NODE_CREATED, Node::find(1)));
        $event = Cache::get("t.val");
        event($event);
        dd($event);
    }

    public function testPagination(Request $request)
    {
        $resource = $request->get('resource');
        $perPage = $request->get('perPage');

        if ($perPage == null || empty($perPage))
            $perPage = config('pagination.default_per_page');

        return \App\Libraries\PaginationManager::paginate($resource, $perPage);
    }
}