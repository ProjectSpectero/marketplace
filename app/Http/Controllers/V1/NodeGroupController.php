<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\NodeMarketModel;
use App\Constants\OrderStatus;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use App\Libraries\PaginationManager;
use App\Libraries\SearchManager;
use App\Node;
use App\NodeGroup;
use App\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        /** @var NodeGroup $nodeGroup */
        $nodeGroup = NodeGroup::findOrFail($id);
        $this->authorizeResource($nodeGroup);

        $rules = [
            'friendly_name' => 'sometimes|alpha_dash',
            'market_model' => [ 'sometimes', Rule::in(NodeMarketModel::getConstants()) ],
            'price' => 'required_with:market_model|numeric|min:5'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        if ($request->has('market_model') && $nodeGroup->market_model != $input['market_model'])
        {
            // Indicating that this is an update
            if ($nodeGroup->getOrders(OrderStatus::ACTIVE)->count() > 0)
                throw new UserFriendlyException(Errors::HAS_ACTIVE_ORDERS);
        }

        foreach ($input as $key => $value)
        {
            // Why this check? Because we have fields that are /sometimes/ required.
            if ($request->has($key))
                $nodeGroup->$key = $value;
        }

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
        /** @var NodeGroup $nodeGroup */
        $nodeGroup = NodeGroup::findOrFail($id);
        $this->authorizeResource($nodeGroup);

        // A node group for which at least one active order exists cannot be destroyed. Cancel the order first.
        if ($nodeGroup->getOrders(OrderStatus::ACTIVE)->count() != 0)
            throw new UserFriendlyException(Errors::ORDERS_EXIST, ResponseType::FORBIDDEN);

        if (!is_null($nodeGroup->nodes->first()))
            throw new UserFriendlyException(Errors::HAS_NODES, ResponseType::FORBIDDEN);

        $nodeGroup->delete();

        return $this->respond(null, [], Messages::NODE_GROUP_DELETED, ResponseType::NO_CONTENT);
    }

    public function show (Request $request, int $id, String $action = null) : JsonResponse
    {
        /** @var NodeGroup $nodeGroup */
        $nodeGroup = NodeGroup::findOrFail($id);
        $this->authorizeResource($nodeGroup);

        switch ($action)
        {
            case 'engagements':
                return PaginationManager::paginate($request, $nodeGroup->getEngagements()->noEagerLoads());
            default:
                return $this->respond($nodeGroup->toArray());
        }
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
