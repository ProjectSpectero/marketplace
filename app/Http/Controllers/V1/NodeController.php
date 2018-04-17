<?php


namespace App\Http\Controllers\V1;


use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\Messages;
use App\Constants\NodeMarketModel;
use App\Constants\NodeStatus;
use App\Constants\NodeSyncStatus;
use App\Constants\OrderStatus;
use App\Constants\Protocols;
use App\Constants\ResponseType;
use App\Errors\FatalException;
use App\Errors\UserFriendlyException;
use App\Events\NodeEvent;
use App\HistoricResource;
use App\Libraries\NodeManager;
use App\Libraries\PaginationManager;
use App\Node;
use App\Libraries\SearchManager;
use App\OrderLineItem;
use App\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class NodeController extends CRUDController
{
    public function __construct()
    {
        $this->resource = 'node';
    }

    public function show (Request $request, int $id, String $action = null) : JsonResponse
    {
        /** @var Node $node */
        $node = Node::findOrFail($id);
        $this->authorizeResource($node);

        $data = null;

        switch ($action)
        {
            case 'engagements':
                return PaginationManager::paginate($request, $node->getEngagements()->noEagerLoads());

            case 'services':
                return PaginationManager::paginate($request, Service::where('node_id', $node->id));

            case 'ips':
                $data = $node->ipAddresses()->get()->toArray();
                break;

            case 'config-pull':
                $activeEngagements = $node->getEngagements(OrderStatus::ACTIVE)
                    ->select([ 'order_line_items.id', 'orders.accessor', 'order_line_items.sync_timestamp' ])
                    ->get();

                $data = [];
                foreach ($activeEngagements as $engagement)
                {
                    $accessor = $engagement->accessor;
                    if ($accessor != null)
                    {
                        list($username, $password) = explode(':', $accessor);

                        // TODO: take the node's configured BCrypt strength into account eventually while doing this
                        $password = Hash::make($password);

                        $data[] = [
                            'engagement_id' => $engagement->id,
                            'username' => $username,
                            'password' => $password,
                            'sync_timestamp' => $engagement->sync_timestamp,
                            'cert' => "",
                            'cert_key' => "" //TODO: actually issue certs as soon as OpenVPN is operational.
                        ];

                        $lineItem = OrderLineItem::findOrFail($engagement->id);
                        $lineItem->sync_status = NodeSyncStatus::SYNCED;
                        $lineItem->sync_timestamp = Carbon::now();

                        $lineItem->saveOrFail();
                    }
                }
                break;

            case 'config-full':
                break;

            case 'auth':
                if ($node->status !== NodeStatus::CONFIRMED)
                    throw new UserFriendlyException(Errors::NODE_PENDING_VERIFICATION);

                $data = $this->getFromCacheOrGenerateAuthTokens($node);
                break;

            default:
                $data = $node->toArray();
        }

        return $this->respond($data);
    }

    public function index(Request $request) : JsonResponse
    {
        $this->authorizeResource();
        $rules = [
            'searchId' => 'sometimes|alphanum'
        ];
        $this->validate($request, $rules);

        $queryBuilder = SearchManager::process($request, 'node');

        return PaginationManager::paginate($request, $queryBuilder);
    }

    public function self(Request $request, String $action = null)
    {
        $user = $request->user();
        $queryBuilder = SearchManager::process($request, 'node')->where('user_id', $user->id);

        if ($action != null && $action == 'uncategorized')
            $queryBuilder->where('group_id', null);

        return PaginationManager::paginate($request, $queryBuilder);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeResource();
        $rules = [
            'protocol' => [ 'required', Rule::in(Protocols::getConstants())],
            'ip' => 'sometimes|ip',
            'port' => 'required|integer|min:1024|max:65534',
            'access_token' => 'required|min:5|regex:/[a-zA-Z0-9-_]+:[a-zA-Z0-9-_]+$/',
            'install_id' => 'required|alpha_dash|size:36'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);
        $ipAddress = $request->input('ip', $request->ip());

        try
        {
            /** @var Node $node */
            $node = Node::findByIPOrInstallIdOrFail($input['install_id'], $ipAddress);
            if ($node != null)
            {
                $data = null;
                if ($node->user_id == $request->user()->id)
                {
                    $message = Messages::RESOURCE_ALREADY_EXISTS_ON_OWN_ACCOUNT;
                    $data = $node->toArray();
                }

                else
                    $message = Errors::REQUEST_FAILED;

                return $this->respond($data, [ Errors::RESOURCE_ALREADY_EXISTS ], $message, ResponseType::CONFLICT);
            }
        }
        catch (ModelNotFoundException $silenced)
        {
            // This means node doesn't exist, we're clear to proceed.
            // Add back IP (if not provided)
            // TODO: consider storing access_token encrypted
            $input['ip'] = $ipAddress;
            $input['status'] = NodeStatus::UNCONFIRMED;
            $node = Node::create($input);
            $node->user_id = $request->user()->id;
            $node->market_model = NodeMarketModel::UNLISTED;
            $node->saveOrFail();
        }

        event(new NodeEvent(Events::NODE_CREATED, $node, []));
        return $this->respond($node->toArray(), [], null, ResponseType::CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var Node $node */
        $node = Node::findOrFail($id);
        $this->authorizeResource($node);

        $rules = [
            'protocol' => [ 'required', Rule::in(Protocols::getConstants()) ],
            'ip' => 'required|ip',
            'port' => 'required|integer|min:1024|max:65534',
            'access_token' => 'sometimes|min:5|regex:/[a-zA-Z0-9-_]+:[a-zA-Z0-9-_]+$/',
            'friendly_name' => 'sometimes|alpha_dash',
            'market_model' => [ 'sometimes', Rule::in(NodeMarketModel::getConstants()) ],
            'price' => 'required_with:market_model|numeric|min:5'
        ];

        $reverifyRules = [
            'ip', 'port', 'access_token', 'protocol'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        if ($request->has('market_model') && $node->market_model != $input['market_model'])
        {
            // Indicating that this is an update
            if ($node->getOrders(OrderStatus::ACTIVE)->count() > 0)
                throw new UserFriendlyException(Errors::HAS_ACTIVE_ORDERS);
        }

        foreach ($input as $key => $value)
        {
            // Why this check? Because we have fields that are /sometimes/ required.
            if ($request->has($key))
            {
                $node->$key = $value;
                if (in_array($key, $reverifyRules))
                    $node->status = NodeStatus::PENDING_VERIFICATION;
            }
        }

        $node->saveOrFail();

        event(new NodeEvent(Events::NODE_REVERIFY, $node));
        return $this->respond($node->toArray(), [], Messages::NODE_UPDATED);
    }


    /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @throws UserFriendlyException
     * @throws \Throwable
     */
    public function reverify (Request $request, int $id) : JsonResponse
    {
        $node = Node::findOrFail($id);

        $err = "";
        switch ($node->status)
        {
            case NodeStatus::CONFIRMED:
                $err = Errors::NODE_ALREADY_VERIFIED;
                break;
            case NodeStatus::PENDING_VERIFICATION:
                $err = Errors::NODE_PENDING_VERIFICATION;
                break;
        }
        if (! empty($err))
            throw new UserFriendlyException($err, ResponseType::CONFLICT);

        $node->status = NodeStatus::PENDING_VERIFICATION;
        $node->saveOrFail();

        event(new NodeEvent(Events::NODE_REVERIFY, $node));
        return $this->respond(null, [], Messages::NODE_VERIFICATION_QUEUED);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var Node $node */
        $node = Node::findOrFail($id);
        $this->authorizeResource($node);

        // A node group for which at least one active order exists cannot be destroyed. Cancel the order first.
        if ($node->getOrders(OrderStatus::ACTIVE)->count() != 0)
            throw new UserFriendlyException(Errors::ORDERS_EXIST, ResponseType::FORBIDDEN);

        HistoricResource::createCopy($node, ['services', 'ipAddresses'], $request->user());
        $this->removeNodeServicesAndIPAddresses($node);

        $node->delete();
        event(new NodeEvent(Events::NODE_DELETED, $node));
        return $this->respond(null, [], Messages::USER_DESTROYED, ResponseType::NO_CONTENT);
    }

    private function removeNodeServicesAndIPAddresses(Node $node)
    {
        foreach ($node->services as $service)
            $service->delete();

        foreach ($node->ipAddresses as $addr)
            $addr->delete();
    }

    private function getFromCacheOrGenerateAuthTokens (Node $node)
    {
        $key = $this->formulateCacheKey($node);
        if (\Cache::has($key))
            return \Cache::get($key);

        // OK, it ain't in the cache.
        $data = [];
        try
        {
            $manager = new NodeManager($node, true);
            $data['credentials'] = $manager->getTokens();
            $data['meta'] = [
                'protocol' => $node->protocol,
                'ip' => $node->ip,
                'port' => $node->port,
                'apiVersion' => 'v1' // TODO: Resolve this from the daemon version
            ];
        }
        catch (\Exception $error)
        {
            throw new UserFriendlyException(Errors::NODE_UNREACHABLE, ResponseType::SERVICE_UNAVAILABLE);
        }

        \Cache::put($key, $data, env('NODE_JWT_CACHE_MINUTES', 10));
        return $data;
    }

    private function formulateCacheKey (Node $node)
    {
        return 'node.auth.tokens.' . $node->id;
    }
}