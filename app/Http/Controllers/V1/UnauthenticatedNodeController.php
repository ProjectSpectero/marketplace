<?php


namespace App\Http\Controllers\V1;


use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Constants\UserStatus;
use App\Errors\UserFriendlyException;
use App\Node;
use App\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class UnauthenticatedNodeController extends V1Controller
{
    private $controller;

    /*
     * What's the purpose here? To allow daemon nodes to be added without authentication / take care of other node related activities
     * Verification here is primarily based on two things: the user's node key, and the node's own ID + identity string
     */

    public function __construct()
    {
        $this->controller = new NodeController();
    }

    public function create (Request $request)
    {
        $req = $this->prepareRequest($request);
        return $this->controller->store($req, true);
    }

    public function handleConfigPush (Request $request, int $id, String $action)
    {
        $req = $this->prepareRequest($request);
        $rules = [
            'install_id' => 'required|alpha_dash',
        ];
        $this->validate($request, $rules);
        $data = $this->cherryPick($request, $rules);

        $node = Node::findOrFail($id);

        if ($node->user_id != $req->user()->id || ! in_array($action, [ 'config-pull', 'config-full']))
            throw new UserFriendlyException(Errors::UNAUTHORIZED, ResponseType::FORBIDDEN);

        if ($node->install_id !== $data['install_id'])
            throw new UserFriendlyException(Errors::IDENTITY_MISMATCH, ResponseType::FORBIDDEN);

        return $this->controller->show($request, $id, $action);
    }

    private function prepareRequest (Request $request) : Request
    {
        $rules = [
            'node_key' => 'required|alpha_dash'
        ];
        $this->validate($request, $rules);
        $data = $this->cherryPick($request, $rules);

        try
        {
            $user = User::findByNodeKey($data['node_key'], true);
        }
        catch (ModelNotFoundException $silenced)
        {
            throw new UserFriendlyException(Errors::INVALID_NODE_KEY);
        }

        if ($user->status != UserStatus::ACTIVE)
            throw new UserFriendlyException(Errors::UNAUTHORIZED);

        $request->setUserResolver(function () use ($user)
        {
            return $user;
        });

        \Auth::setUser($user);

        return $request;
    }
}