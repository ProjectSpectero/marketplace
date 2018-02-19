<?php


namespace App\Http\Controllers\V1;


use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\Messages;
use App\Constants\NodeStatus;
use App\Constants\Protocols;
use App\Constants\ResponseType;
use App\Events\NodeEvent;
use App\Libraries\PaginationManager;
use App\Node;
use App\Libraries\SearchManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NodeController extends CRUDController
{
    public function __construct()
    {
        $this->resource = 'node';
    }

    public function reverify (Request $request, int $id) : JsonResponse
    {
        $node = Node::findOrFail($id);

        if ($node->status != NodeStatus::UNCONFIRMED)
            $this->respond(null, [ Errors::NODE_ALREADY_VERIFIED ], Errors::REQUEST_FAILED, ResponseType::CONFLICT);

        event(new NodeEvent(Events::NODE_REVERIFY, $node));
        return $this->respond(null, [], Messages::NODE_VERIFICATION_QUEUED);
    }

    public function show (Request $request, int $id) : JsonResponse
    {
        /** @var Node $node */
        $node = Node::findOrFail($id);
        $this->authorizeResource($node);

        return $this->respond($node->toArray());
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

    public function store(Request $request): JsonResponse
    {
        $this->authorizeResource();
        $rules = [
            'protocol' => [ 'required', Rule::in(Protocols::getConstants())],
            'ip' => 'sometimes|ip',
            'port' => 'required|integer|min:1024|max:65534',
            'access_token' => 'required|min:5|max:72|regex:/[a-zA-Z0-9-_]+:.+$/',
            'install_id' => 'required|alpha_dash|size:36'
        ];

        //Puposefully not validating unique:nodes,install_id so we can return a 409/conflict instead to signal to the daemon that you're already registered
        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);
        $ipAddress = $request->input('ip', $request->ip());

        try
        {
            $node = Node::findByIPOrInstallIdOrFail($input['install_id'], $ipAddress);
            if ($node != null)
            {
                if ($node->user_id == $request->user()->id)
                    $message = Messages::RESOURCE_ALREADY_EXISTS_ON_OWN_ACCOUNT;
                else
                    $message = Errors::REQUEST_FAILED;

                return $this->respond(null, [ Errors::RESOURCE_ALREADY_EXISTS ], $message, ResponseType::CONFLICT);
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
        }

        event(new NodeEvent(Events::NODE_CREATED, $node, []));
        return $this->respond($node->toArray(), [], null, ResponseType::CREATED); // TODO: Change the autogenerated stub
    }

    public function self(Request $request)
    {
        $user = $request->user();
        return PaginationManager::paginate($request, Node::findForUser($user->id));
    }
}