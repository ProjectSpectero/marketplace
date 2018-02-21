<?php

namespace App\Http\Controllers\V1;

use App\Constants\Messages;
use App\Constants\OrderStatus;
use App\Constants\ResponseType;
use App\Libraries\PaginationManager;
use App\Libraries\SearchManager;
use App\Node;
use App\NodeGroup;
use App\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NodeGroupController extends CRUDController
{

    public function __construct()
    {
        $this->resource = 'node_group';
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeResource();

        $rules = [
            'searchId' => 'sometimes|alphanum'
        ];

        $this->validate($request, $rules);

        $queryBuilder = SearchManager::process($request, $this->resource);

        return PaginationManager::paginate($request, $queryBuilder);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeResource();

        $rules = [
            'friendly_name' => 'required',
            'status' => 'required',
            'market_model' => 'required',
            'price' => 'required'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $nodeGroup = new NodeGroup();
        $nodeGroup->friendly_name = $input['friendly_name'];
        $nodeGroup->status = $input['status'];
        $nodeGroup->user_id = $request->user()->id;
        $nodeGroup->market_model = $input['market_model'];
        $nodeGroup->price = $input['price'];

        $nodeGroup->saveOrFail();

        return $this->respond($nodeGroup->toArray(), [], null, ResponseType::CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rules = [
            'friendly_name' => 'required',
            'status' => 'required',
            'market_model' => 'required',
            'price' => 'required'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $nodeGroup = NodeGroup::findOrFail($id);

        $this->authorizeResource($nodeGroup);

        foreach ($input as $key => $value)
            $nodeGroup->$key = $value;

        $nodeGroup->user_id = $request->user()->id;

        $nodeGroup->saveOrFail();

        return $this->respond($nodeGroup->toArray(), [], Messages::NODE_GROUP_UPDATED);
    }

    public function self(Request $request)
    {
        $user = $request->user();
        return PaginationManager::paginate($request, NodeGroup::findForUser($user->id));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $nodeGroup = NodeGroup::findOrFail($id);
        $this->authorizeResource($nodeGroup);

        $nodeGroup->delete();

        return $this->respond(null, [], Messages::NODE_GROUP_DELETED, ResponseType::NO_CONTENT);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $nodeGroup = NodeGroup::findOrFail($id);
        $this->authorizeResource($nodeGroup);

        return $this->respond($nodeGroup->toArray());
    }

    public function assign(Request $request)
    {
        $groupId = $request->get('group_id');
        $nodeGroup = NodeGroup::findOrFail($groupId);

        $node = Node::findOrFail($request->get('node_id'));

        $this->authorizeResource($node, 'node.assign');
        $this->authorizeResource($nodeGroup, 'node_group.assign');

        $rules = [
            'node_id' => 'required',
            'group_id' => 'required'
        ];

        $this->validate($request, $rules);

        $node->group_id = $groupId;

        $node->saveOrFail();

        return $this->respond($node->toArray(), [], Messages::NODE_ASSIGNED);
    }

}
