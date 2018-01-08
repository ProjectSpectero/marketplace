<?php

namespace App\Http\Controllers\V1;

use App\Constants\ResponseType;
use App\Libraries\SearchManager;
use App\User;
use App\UserMeta;
use App\Constants\Messages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends CRUDController
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
            $results = SearchManager::process($searchId, 'user');
            return $this->respond($results->toArray());
        }

        return $this->respond(User::all()->toArray());
    }

    public function store(Request $request) : JsonResponse
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'sometimes|min:5|max:72',
            'address_line_1' => 'required|max:255',
            'address_line_2' => 'required|max:255',
            'city' => 'required|max:255',
            'state' => 'required|max:255',
            'post_code' => 'required|alpha_num|max:255',
            'country' => 'required|country|max:255',
            'phone_no' => 'required|max:255'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        /** @var User $user */
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email']
        ]);
        $user->password = Hash::make($input['password']);
        $user->saveOrFail();

        // Remove the ones that go into the original model
        unset($input['name'], $input['email'], $input['password']);

        foreach ($input as $key => $value)
            UserMeta::addOrUpdateMeta($user, $key, $value);

        return $this->respond($user->toArray(), [], Messages::USER_CREATED, ResponseType::CREATED);
    }

    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        return $this->respond($user->toArray());
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $request->get('id'),
            'password' => 'sometimes|min:5|max:72',
            'address_line_1' => 'required|max:255',
            'address_line_2' => 'required|max:255',
            'city' => 'required|max:255',
            'state' => 'required|max:255',
            'post_code' => 'required|alpha_num|max:255',
            'country' => 'required|country|max:255',
            'phone_no' => 'required|max:255'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $user->name = $input['name'];
        $user->email = $input['email'];

        if (isset($input['password']))
            $user->password = Hash::make($input['password']);

        // Remove the ones that go into the original model
        unset($input['name'], $input['email'], $input['password']);

        foreach ($input as $key => $value)
            UserMeta::addOrUpdateMeta($user, $key, $value);

        $user->saveOrFail();

        return $this->respond($user->toArray(), [], Messages::USER_UPDATED);
    }

    public function destroy(int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        $user->delete();

        return $this->respond(null, [], Messages::USER_DESTROYED, ResponseType::NO_CONTENT);
    }
}
