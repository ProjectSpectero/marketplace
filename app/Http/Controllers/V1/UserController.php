<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Errors\NotSupportedException;
use App\User;
use App\UserMeta;
use App\Constants\Messages;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends CRUDController
{
    public function index() : JsonResponse
    {
        return $this->respond(User::all(), [], Messages::GET_USERS_LIST);
    }

    public function store(Request $request) : JsonResponse
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'address_line_1' => 'sometimes|required',
            'address_line_2' => 'sometimes|required',
            'city' => 'sometimes|required',
            'state' => 'sometimes|required',
            'post_code' => 'sometimes|required',
            'country' => 'sometimes|country',
            'phone_no' => 'sometimes|required'
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
        $user = User::findOrFail($id);

        return $this->respond($user, [], Messages::GET_USER);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $rules = [
            'name' => 'sometimes|required',
            'email' => 'sometimes|required|email'.$request->get('id'),
            'password' => 'sometimes|required|min:5|max:72',
            'address_line_1' => 'sometimes|required',
            'address_line_2' => 'sometimes|required',
            'city' => 'sometimes|required',
            'state' => 'sometimes|required',
            'post_code' => 'sometimes|required',
            'country' => 'sometimes|country',
            'phone_no' => 'sometimes|required'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $user->name = $input['name'];
        $user->email = $input['email'];
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
        $user = User::findOrFail($id);

        $user->delete();

        $this->respond(null, [], Messages::USER_DESTROYED, ResponseType::NO_CONTENT);
    }
}
