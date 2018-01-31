<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Constants\UserRoles;
use App\Constants\UserStatus;
use App\Events\UserEvent;
use App\Libraries\PaginationManager;
use App\Libraries\PermissionManager;
use App\Libraries\SearchManager;
use App\Libraries\Utility;
use App\User;
use App\UserMeta;
use App\Constants\Messages;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Phalcon\Config\Adapter\Json;

class UserController extends CRUDController
{
    public function __construct()
    {
        $this->resource = 'user';
    }

    public function self (Request $request) : JsonResponse
    {
        $user = $request->user();
        return $this->respond(
            array_merge($user->toArray(),UserMeta::getUserPublicMeta($user))
        );
    }

    public function index(Request $request) : JsonResponse
    {
        $this->authorizeResource();
        $rules = [
            'searchId' => 'sometimes|alphanum'
        ];
        $this->validate($request, $rules);

        $queryBuilder = SearchManager::process($request, 'user');

        return PaginationManager::paginate($request, $queryBuilder);
    }

    public function store(Request $request) : JsonResponse
    {
        // user.create permission not applied, since this is an anonymous registration route

        $rules = [
            'name' => 'sometimes|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:5|max:72',
            UserMetaKeys::AddressLineOne => 'sometimes|max:255',
            UserMetaKeys::AddressLineTwo => 'sometimes|max:255',
            UserMetaKeys::City => 'sometimes|max:255',
            UserMetaKeys::State => 'sometimes|max:255',
            UserMetaKeys::PostCode => 'sometimes|alpha_num|max:255',
            UserMetaKeys::Country => 'sometimes|country|max:255',
            UserMetaKeys::PhoneNumber => 'sometimes|max:255',
            UserMetaKeys::Organization => 'sometimes|max:255',
            UserMetaKeys::TaxIdentification => 'sometimes|max:255'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        /** @var User $user */
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'status' => UserStatus::EMAIL_VERIFICATION_NEEDED
        ]);
        $user->password = Hash::make($input['password']);
        $user->status = UserStatus::EMAIL_VERIFICATION_NEEDED;
        $user->node_key = Utility::getRandomString(2);
        $user->saveOrFail();

        // Remove the ones that go into the original model
        unset($input['name'], $input['email'], $input['password']);

        foreach ($input as $key => $value)
            UserMeta::addOrUpdateMeta($user, $key, $value);

        PermissionManager::assign($user, UserRoles::USER);

        event(new UserEvent(Events::USER_CREATED, $user));

        return $this->respond($user->toArray(), [], Messages::USER_CREATED, ResponseType::CREATED);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        // This is done to allow the user to view themselves, since we can't mark an user to be able to 'own' other users
        if ($request->user()->id != $id)
            $this->authorizeResource();

        return $this->respond($user->toArray());
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        // This is done to allow the user to update themselves, since we can't mark an user to be able to 'own' other users
        if ($request->user()->id != $id)
            $this->authorizeResource();

        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $request->get('id'),
            'password' => 'sometimes|min:5|max:72',
            UserMetaKeys::AddressLineOne => 'sometimes|max:255',
            UserMetaKeys::AddressLineTwo => 'sometimes|max:255',
            UserMetaKeys::City => 'sometimes|max:255',
            UserMetaKeys::State => 'sometimes|max:255',
            UserMetaKeys::PostCode => 'sometimes|alpha_num|max:255',
            UserMetaKeys::Country => 'sometimes|country|max:255',
            UserMetaKeys::PhoneNumber => 'sometimes|max:255',
            UserMetaKeys::Organization => 'sometimes|max:255',
            UserMetaKeys::TaxIdentification => 'sometimes|max:255'
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

        event(new UserEvent(Events::USER_UPDATED, $user));
        return $this->respond($user->toArray(), [], Messages::USER_UPDATED);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeResource();

        /** @var User $user */
        try
        {
            $user = User::findOrFail($id);
        }
        catch (ModelNotFoundException $silenced)
        {
            return $this->respond(null, [Errors::RESOURCE_NOT_FOUND], ResponseType::NOT_FOUND);
        }

        $user->delete();

        event(new UserEvent(Events::USER_DELETED, $user));
        return $this->respond(null, [], Messages::USER_DESTROYED, ResponseType::NO_CONTENT);
    }

    public function verify(Request $request, String $email, String $token): JsonResponse
    {
        $failed = false;
        try
        {
            /** @var User $user */
            $user = User::where('email', $email)
                ->firstOrFail();
        }
        catch (ModelNotFoundException $silenced)
        {
            $failed = true;
        }

        if (! $failed)
        {
            if ($user->status == UserStatus::EMAIL_VERIFICATION_NEEDED)
            {
                $verifyToken = UserMeta::loadMeta($user, UserMetaKeys::VerifyToken, true)->meta_value;

                if ($verifyToken !== $token)
                    return $this->respond(
                        null, [ Errors::USER_VERIFICATION_FAILED ], null, ResponseType::NOT_AUTHORIZED);

                $user->status = UserStatus::ACTIVE;
                $user->saveOrFail();

                UserMeta::deleteMeta($user, UserMetaKeys::VerifyToken);

                return $this->respond(null, [], Messages::USER_VERIFIED, ResponseType::OK);
            }
            else
                return $this->respond(
                    null, [ Errors::USER_ALREADY_VERIFIED ], Messages::USER_VERIFIED, ResponseType::BAD_REQUEST
                );
        }

        return $this->respond(
            null, [ Errors::USER_VERIFICATION_FAILED ], null, ResponseType::NOT_AUTHORIZED);
    }

}
