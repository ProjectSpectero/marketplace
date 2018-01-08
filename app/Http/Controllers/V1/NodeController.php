<?php


namespace App\Http\Controllers\V1;


use App\Node;
use App\Libraries\SearchManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NodeController extends CRUDController
{
    public function index(Request $request) : JsonResponse
    {
        $rules = [
            'searchId' => 'sometimes|alphanum'
        ];
        $this->validate($request, $rules);

        if ($request->has('searchId'))
        {
            $searchId = $request->input('searchId');
            $results = SearchManager::process($searchId, 'node');
            return $this->respond($results->toArray());
        }

        return $this->respond(Node::all()->toArray());
    }
}