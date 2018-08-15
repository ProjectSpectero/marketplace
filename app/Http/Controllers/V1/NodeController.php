<?php


namespace App\Http\Controllers\V1;


use App\Constants\DaemonVersion;
use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\Messages;
use App\Constants\NodeMarketModel;
use App\Constants\NodeStatus;
use App\Constants\NodeSyncStatus;
use App\Constants\OrderStatus;
use App\Constants\Protocols;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use App\Events\NodeEvent;
use App\HistoricResource;
use App\Libraries\CommandProxyManager;
use App\Libraries\NodeManager;
use App\Libraries\PaginationManager;
use App\Mail\NodeAdded;
use App\Node;
use App\Libraries\SearchManager;
use App\OrderLineItem;
use App\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha512;

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

            case 'resources':
                $nodeResources = [];
                foreach ($node->services as $service)
                {
                    $nodeResources[] = [
                        'type' => $service->type,
                        'resource' => $service->connection_resource
                    ];
                }

                $data = $nodeResources;
                break;

            case 'ips':
                $data = $node->ipAddresses()->get()->toArray();
                break;

            case 'config-pull':
                $selectTargets = [ 'order_line_items.id', 'orders.accessor', 'order_line_items.sync_timestamp' ];
                $activeEngagements = $node->getEngagements(OrderStatus::ACTIVE)
                    ->select($selectTargets)
                    ->get();

                if ($node->group != null)
                {
                    $activeEngagements = $activeEngagements->merge($node->group->getEngagements(OrderStatus::ACTIVE)
                                                                       ->select($selectTargets)
                                                                       ->get());
                }

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

                        $lineItem = OrderLineItem::find($engagement->id);

                        if ($lineItem != null)
                        {
                            $lineItem->sync_status = NodeSyncStatus::SYNCED;
                            $lineItem->sync_timestamp = Carbon::now();

                            $lineItem->saveOrFail();
                        }
                        else
                            Log::warning("Could not locate lineItem -> $engagement->id despite expecting to find it. Sync status unupdated.");

                    }
                }
                break;

            case 'config-full':
                break;

            case 'auth':
                $data = $this->getFromCacheOrGenerateAuthTokens($node, $request->has('direct'));
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

    public function store(Request $request, bool $indirect = false): JsonResponse
    {
        $this->authorizeResource();
        $rules = [
            'protocol' => [ 'required', Rule::in(Protocols::getConstants())],
            'ip' => 'sometimes|ip',
            'port' => 'required|integer|min:1024|max:65534',
            'access_token' => 'required|min:5|regex:/[a-zA-Z0-9-_]+:[a-zA-Z0-9-_]+$/',
            'install_id' => 'required|alpha_dash|size:36',
            'version' => [ 'required', Rule::in(DaemonVersion::getConstants()) ],
            'system_data' => 'required|array',
            'system_data.CPU' => 'required|array',
            'system_data.Memory' => 'required|array',
            'system_data.Environment' => 'required|array'
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
            $node->version = $input['version'];
            $node->system_data = $input['system_data'];
            $node->saveOrFail();
        }

        // Why here instead of NodeEventListener? That's because there happens to be some collapsed handling there for REVERIFY + CREATED.
        Mail::to($node->user->email)->queue(new NodeAdded($node));

        event(new NodeEvent(Events::NODE_CREATED, $node, []));

        if ($indirect)
        {
            // Hide attributes unauth/node should not see.
            $node->makeHidden([ 'user_id', 'user' ]);
        }

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
            'friendly_name' => 'sometimes|alpha_dash_spaces|max:64',
            'market_model' => [ 'sometimes', Rule::in(NodeMarketModel::getConstraints()) ]
        ];

        if ($request->has('price'))
        {
            if ($request->has('market_model'))
                $marketModel = $request->get('market_model');
            else
                $marketModel = $node->market_model;

            if (in_array($marketModel, NodeMarketModel::getMarketable()))
                $rules['price'] = 'numeric|between:' . env('MIN_RESOURCE_PRICE', 5) . ',' . env('MAX_RESOURCE_PRICE', 9999);
        }

        $reverifyRules = [
            'ip', 'port', 'access_token', 'protocol'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        if ($request->has('market_model'))
        {
            // Indicating that this is an update
            if ($node->market_model != $input['market_model'] &&
                $node->getOrders(OrderStatus::ACTIVE)->count() > 0)
            {
                throw new UserFriendlyException(Errors::HAS_ACTIVE_ORDERS);
            }
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
        /** @var Node $node */
        $node = Node::findOrFail($id);

        if ($node->status !== NodeStatus::UNCONFIRMED)
            throw new UserFriendlyException(Errors::NODE_STATUS_MISMATCH);

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
        if ($node->getOrders(OrderStatus::ACTIVE)->count() > 0)
            throw new UserFriendlyException(Errors::ORDERS_EXIST, ResponseType::FORBIDDEN);

        HistoricResource::createCopy($node, ['services', 'ipAddresses'], $node->user);

        $node->status = NodeStatus::PENDING_DELETION;
        $node->saveOrFail();

        event(new NodeEvent(Events::NODE_DELETED, $node));
        return $this->respond(null, [], Messages::NODE_DELETED, ResponseType::NO_CONTENT);
    }

    private function getFromCacheOrGenerateAuthTokens (Node $node, bool $direct = false)
    {
        $key = $this->formulateCacheKey($node, $direct);
        if (\Cache::has($key))
            return \Cache::get($key);

        // OK, it ain't in the cache.
        $data = [];
        try
        {
            $tokenCollection = NodeManager::generateAuthTokens($node);

            $accessExpires = $tokenCollection['access']['expires'];

            // The cached token will expire 2 minutes before the real expiry, to the minute caused some sync issues.
            /** @var Carbon $minutesTillAccessExpires */
            $minutesTillAccessExpires = Carbon::now()->diffInMinutes(Carbon::createFromTimestamp($accessExpires)) - 2;

            // $refreshExpires = $tokenCollection['refresh']['expires'];

            if ($direct)
            {
                $protocol = $node->protocol;
                $ip = $node->ip;
                $port = $node->port;
                $data['credentials'] = $tokenCollection;
            }
            else
            {
                $protocol = 'https';
                $ip = CommandProxyManager::resolve($node);
                $port = 443;

                $proxyAuthorization = $tokenCollection;

                // Need to build the right JWT payload to interact with the daemon-proxy now.
                $signer = new Sha512();
                $token = (new Builder())
                    ->setIssuer(env('APP_URL', "https://cloud.spectero.com"))
                    ->setId(uniqid("", true))
                    ->setIssuedAt(time())
                    ->setNotBefore(time())
                    ->setExpiration($accessExpires)
                    ->set('proxy.payload', [
                        'id' => $node->id,
                        'protocol' => $node->protocol,
                        'ip' => $node->ip,
                        'port' => $node->port,
                        'credentials' => $tokenCollection
                    ])
                    ->sign($signer, env('NODE_CPROXY_JWT_SIGNING_KEY'))
                    ->getToken();

                $proxyAuthorization['access']['token'] = (string) $token;
                $data['credentials'] = $proxyAuthorization;
            }

            $data['meta'] = [
                'protocol' => $protocol,
                'ip' => $ip,
                'port' => $port,
                'apiVersion' => 'v1' // TODO: Resolve this from the daemon version
            ];
        }
        catch (\Exception $error)
        {
            throw new UserFriendlyException(Errors::NODE_UNREACHABLE, ResponseType::SERVICE_UNAVAILABLE);
        }

        if ($minutesTillAccessExpires > 0)
            \Cache::put($key, $data, $minutesTillAccessExpires);

        return $data;
    }

    private function formulateCacheKey (Node $node, bool $direct)
    {
        return 'node.auth.tokens.' . $direct . '.' . $node->id;
    }
}