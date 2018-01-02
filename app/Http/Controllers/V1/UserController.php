<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Errors\NotSupportedException;
use App\Errors\UserFriendlyException;
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
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'post_code' => 'sometimes|required|integer',
            'phone_no' => 'sometimes|required'
        ]);

        $input = $request->all();

        if (User::where('email', $input['email'])->exists())
            throw new UserFriendlyException(Errors::RESOURCE_ALREADY_EXISTS, ResponseType::CONFLICT);

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
