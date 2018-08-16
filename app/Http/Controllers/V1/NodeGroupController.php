<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\NodeMarketModel;
use App\Constants\NodeStatus;
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
            'friendly_name' => 'required|alpha_dash_spaces|max:64',
            'market_model' => [ 'required', Rule::in(NodeMarketModel::getConstraints()) ],
        ];

        if ($request->has('price'))
        {
            $marketModel = $request->get('market_model');

            if (in_array($marketModel, NodeMarketModel::getMarketable()))
                $rules['price'] = 'numeric|between:' . env('MIN_RESOURCE_PRICE', 5) . ',' . env('MAX_RESOURCE_PRICE', 9999);
        }

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $nodeGroup = new NodeGroup();
        $nodeGroup->friendly_name = $input['friendly_name'];
        $nodeGroup->status = NodeStatus::CONFIRMED;
        $nodeGroup->user_id = $request->user()->id;
        $nodeGroup->market_model = $input['market_model'];
        $nodeGroup->price = $input['price'] ?? 0;

        $nodeGroup->saveOrFail();

        return $this->respond($nodeGroup->toArray(), [], null, ResponseType::CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var NodeGroup $nodeGroup */
        $nodeGroup = NodeGroup::findOrFail($id);
        $this->authorizeResource($nodeGroup);

        $rules = [
            'friendly_name' => 'sometimes|alpha_dash_spaces|max:64',
            'market_model' => [ 'sometimes', Rule::in(NodeMarketModel::getConstraints()) ],
        ];

        if ($request->has('price'))
        {
            if ($request->has('market_model'))
                $marketModel = $request->get('market_model');
            else
                $marketModel = $nodeGroup->market_model;

            if (in_array($marketModel, NodeMarketModel::getMarketable()))
                $rules['price'] = 'numeric|between:' . env('MIN_RESOURCE_PRICE', 5) . ',' . env('MAX_RESOURCE_PRICE', 9999);
        }

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        if ($request->has('market_model') && $nodeGroup->market_model != $input['market_model'])
        {
            // Indicating that this is an update
            if ($nodeGroup->getOrders(OrderStatus::ACTIVE)->count() > 0)
                throw new UserFriendlyException(Errors::HAS_ACTIVE_ORDERS, ResponseType::FORBIDDEN);

            // MAR-241: sync market models for underlying nodes when the group's market model is due for change.
            /** @var Node $node */
            foreach ($nodeGroup->nodes as $node)
            {
                $node->market_model = $input['market_model'];
                $node->saveOrFail();
            }
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
        $rules = [
            'searchId' => 'sometimes|alphanum'
        ];
        $this->validate($request, $rules);

        $user = $request->user();
        $query = SearchManager::process($request, 'node_group', NodeGroup::findForUser($user->id));

        return PaginationManager::paginate($request, $query);
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

            case 'resources':
                $nodeResources = [
                    'id' => $nodeGroup->id,
                    'reference' => []
                ];
                foreach ($nodeGroup->nodes as $node)
                {
                    foreach ($node->services as $service)
                    {
                        $nodeResources['reference'][] = [
                            'type' => $service->type,
                            'resource' => $service->connection_resource
                        ];
                    }
                }
                return $this->respond($nodeResources);
            default:
                return $this->respond($nodeGroup->toArray());
        }
    }

    public function assign(Request $request)
    {
        $rules = [
            'node_id' => 'required|integer',
            'group_id' => 'required|integer'
        ];

        $this->validate($request, $rules);

        $node = Node::findOrFail($request->get('node_id'));
        $this->authorizeResource($node, 'node.assign');

        /*
         * Commented out as per MAR-163, we'll revisit this someday.
         *  if ($node->status != NodeStatus::CONFIRMED)
                throw new UserFriendlyException(Errors::NODE_PENDING_VERIFICATION, ResponseType::FORBIDDEN);
         */

        /** @var NodeGroup $oldGroup */
        $oldGroup = $node->group;

        if ($oldGroup != null
        && $oldGroup->getOrders(OrderStatus::ACTIVE)->count() !== 0)
        {
            // OK, this node belonged to a group and is now being re-assigned. It also has active orders, and thus needs to be stopped.
            throw new UserFriendlyException(Errors::HAS_ACTIVE_ORDERS, ResponseType::FORBIDDEN);
        }

        $groupId = $request->get('group_id');

        // This enforces a simple existence check. 0 implies a reset to the "UNCATEGORIZED" pseudo-group.
        if ($groupId != 0)
        {
            $nodeGroup = NodeGroup::findOrFail($groupId);

            // This checks if this user has the assign permission for this group.
            $this->authorizeResource($nodeGroup, 'node_group.assign');

            $node->group_id = $groupId;

            // On a node being assigned to a group, its market model should be synced to that of the group.
            if ($node->market_model != $nodeGroup->market_model)
                $node->market_model = $nodeGroup->market_model;
        }
        else
        {
            $node->group_id = null;
            $node->market_model = NodeMarketModel::UNLISTED;
        }

        $node->saveOrFail();

        return $this->respond($node->toArray(), [], Messages::NODE_ASSIGNED);
    }

}
