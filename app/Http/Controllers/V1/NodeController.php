<?php


namespace App\Http\Controllers\V1;


use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\Protocols;
use App\Constants\ResponseType;
use App\Events\NodeEvent;
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

    public function index(Request $request) : JsonResponse
    {
        $this->authorizeResource();
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

    public function store(Request $request): JsonResponse
    {
        $this->authorizeResource();
        $rules = [
            'protocol' => [ 'required', Rule::in(Protocols::getConstants())],
            'ip' => 'required|ip',
            'port' => 'required|integer|min:1024|max:65534',
            'access_token' => 'required|min:5|max:72|regex:/^[a-zA-Z0-9]+:[a-zA-Z0-9-_]+$/',
            'install_id' => 'required|alpha_num'
        ];

        //Puposefully not validating unique:nodes,install_id so we can return a 409/conflict instead to signal to the daemon that you're already registered
        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);
        try
        {
            $node = Node::findByInstallIdOrFail($input['install_id']);
            if ($node != null)
                return $this->respond(null, [ Errors::RESOURCE_ALREADY_EXISTS ], Errors::REQUEST_FAILED, ResponseType::CONFLICT);
        }
        catch (ModelNotFoundException $silenced)
        {
            // This means node doesn't exist, we're clear to proceed.
            $node = Node::create($input);
        }

        event(new NodeEvent(Events::NODE_CREATED, $node, []));
        return $this->respond($node->toArray(), [], null, ResponseType::CREATED); // TODO: Change the autogenerated stub
    }
}