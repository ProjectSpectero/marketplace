<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Constants\UserRoles;
use App\Constants\UserStatus;
use App\Errors\UserFriendlyException;
use App\Events\UserEvent;
use App\Libraries\BillingUtils;
use App\Libraries\PaginationManager;
use App\Libraries\PermissionManager;
use App\Libraries\SearchManager;
use App\Libraries\Utility;
use App\User;
use App\UserMeta;
use App\Constants\Messages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Phalcon\Config\Adapter\Json;
use phpDocumentor\Reflection\Types\Integer;

class UserController extends CRUDController
{
    public function __construct()
    {
        $this->resource = 'user';
    }

    public function self (Request $request) : JsonResponse
    {
        $user = $request->user();
        $card = UserMeta::loadMeta($user, UserMetaKeys::StoredCardIdentifier);
        $cardInfo = [
            'brand' => null,
            'last4' => null,
            'expires' => null
        ];

        if ($card != null && ! $card instanceof Builder)
            list($cardInfo['brand'], $cardInfo['last4'], $cardInfo['expires']) = explode(' ', $card->meta_value, 3);

        $data = array_merge($user->toArray(), UserMeta::getUserPublicMeta($user));
        $data['card'] = $cardInfo;
        $data['plans'] = BillingUtils::getUserPlans($user);

        $roles = [];

        foreach ($user->roles as $role)
            $roles[] = $role['name'];

        $data['roles'] = $roles;

        $abilities = [];

        foreach ($user->abilities as $ability)
            $abilities[] = $ability['name'];

        $data['abilities'] = $abilities;

        return $this->respond($data);
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
            UserMetaKeys::AddressLineOne => 'sometimes|min:1|max:255',
            UserMetaKeys::AddressLineTwo => 'sometimes|min:1|max:255',
            UserMetaKeys::City => 'sometimes|min:1|max:64',
            UserMetaKeys::State => 'sometimes|min:1|max:64',
            UserMetaKeys::PostCode => 'sometimes|min:1|max:64',
            UserMetaKeys::Country => 'sometimes|country|max:64',
            UserMetaKeys::PhoneNumber => 'sometimes|min:1|max:64',
            UserMetaKeys::Organization => 'sometimes|min:1|max:64',
            UserMetaKeys::TaxIdentification => 'sometimes|min:1|max:96'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        /** @var User $user */
        $user = User::create([
            'name' => isset($input['name']) ? $input['name'] : '',
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
        {
            if (! is_null($value) && $value != "")
                UserMeta::addOrUpdateMeta($user, $key, $value);
        }

        UserMeta::addOrUpdateMeta($user, UserMetaKeys::FirstTimeAuthenticating, true);
        PermissionManager::assign($user, UserRoles::USER);

        event(new UserEvent(Events::USER_CREATED, $user));

        return $this->respond($user->toArray(), [], Messages::USER_CREATED, ResponseType::CREATED);
    }

    public function show (Request $request, int $id, String $action = null) : JsonResponse
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        // This is done to allow the user to view themselves, since we can't mark an user to be able to 'own' other users
        if ($request->user()->id != $id)
            $this->authorizeResource();

        return $this->respond($user->toArray());
    }

    /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @throws \App\Errors\FatalException
     * @throws \Throwable
     */
    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        // This is done to allow the user to update themselves, since we can't mark an user to be able to 'own' other users
        if ($request->user()->id != $id)
            $this->authorizeResource();

        $rules = [
            'name' => 'required|min:1|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'sometimes|min:5|max:72',
            'current_password' => 'required_with:password|min:5|max:72',
            UserMetaKeys::AddressLineOne => 'required|min:1|max:255',
            UserMetaKeys::AddressLineTwo => 'sometimes|max:255',
            UserMetaKeys::City => 'required|min:1|max:64',
            UserMetaKeys::State => 'required|min:1|max:64',
            UserMetaKeys::PostCode => 'required|min:1|max:64',
            UserMetaKeys::Country => 'required|country',
            UserMetaKeys::PhoneNumber => 'sometimes|max:64',
            UserMetaKeys::Organization => 'sometimes|max:64',
            UserMetaKeys::TaxIdentification => 'sometimes|max:96'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        if (isset($input['name']))
            $user->name = $input['name'];

        if ($user->email != $input['email'])
        {
            // User is attempting to change his email address
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::OldEmailAddress, $user->email);
            $user->email = $input['email'];
        }

        if ($request->has('password'))
        {
            if (! Hash::check($input['current_password'], $user->password))
                throw new UserFriendlyException(Errors::CURRENT_PASSWORD_MISMATCH, ResponseType::FORBIDDEN);

            $user->password = Hash::make($input['password']);

            event(new UserEvent(Events::USER_PASSWORD_UPDATED, $user));
        }

        // Remove the ones that go into the original model
        unset($input['name'], $input['email'], $input['password'], $input['current_password']);

        foreach ($input as $key => $value)
        {
            if (empty($value))
                UserMeta::deleteMeta($user, $key);
            else
                UserMeta::addOrUpdateMeta($user, $key, $value);

        }


        $user->saveOrFail();

        event(new UserEvent(Events::USER_UPDATED, $user));
        return $this->respond($user->toArray(), [], Messages::USER_UPDATED);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeResource();

        /** @var User $user */
        $user = User::findOrFail($id);

        $user->delete();

        event(new UserEvent(Events::USER_DELETED, $user));
        return $this->respond(null, [], Messages::USER_DESTROYED, ResponseType::NO_CONTENT);
    }

    public function verify(Request $request, String $email, String $token): JsonResponse
    {
        $failed = false;
        $parsedData = [];
        try
        {
            /** @var User $user */
            $user = User::where('email', $email)
                ->firstOrFail();

            $storedToken = UserMeta::loadMeta($user, UserMetaKeys::VerifyToken, true)->meta_value;
            $parsedData = json_decode($storedToken, true);

            $verifyToken = $parsedData['token'];
        }
        catch (ModelNotFoundException $silenced)
        {
            $failed = true;
        }

        if (! $failed)
        {
            if ($user->status == UserStatus::EMAIL_VERIFICATION_NEEDED)
            {
                if ($verifyToken !== $token || $parsedData['email'] !== $user->email)
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

    public function regenNodeKey(Request $request)
    {
        $user = $request->user();

        $user->node_key = Utility::getRandomString(2);
        $user->saveOrFail();

        return $this->respond($user->toArray(), [], Messages::NODE_KEY_REGENERATED);
    }

}
