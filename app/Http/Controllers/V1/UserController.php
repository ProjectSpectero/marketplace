<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Errors\NotSupportedException;
use App\User;
use App\UserMeta;
use App\Constants\Messages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends CRUDController
{
    public function index() : JsonResponse
    {
        throw new NotSupportedException();
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
        throw new NotSupportedException();
    }

    public function update(Request $request, int $id): JsonResponse
    {
        throw new NotSupportedException();
    }

    public function destroy(int $id): JsonResponse
    {
        throw new NotSupportedException();
    }
}
