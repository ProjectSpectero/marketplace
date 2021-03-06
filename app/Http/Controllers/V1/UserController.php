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
use App\HistoricResource;
use App\Libraries\BillingUtils;
use App\Libraries\PaginationManager;
use App\Libraries\PermissionManager;
use App\Libraries\SearchManager;
use App\Libraries\Utility;
use App\PasswordResetToken;
use App\User;
use App\UserMeta;
use App\Constants\Messages;
use Carbon\Carbon;
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
        $abilities = [];

        foreach ($user->roles as $role)
        {
            $roles[] = $role['name'];

            foreach ($role->abilities as $ability)
            {
                $abilities[] = [ 'name' => $ability['name'], 'only_owned' => $ability['only_owned'] ];
            }
        }

        $data['roles'] = $roles;

        foreach ($user->abilities as $ability)
            $abilities[] = [ 'name' => $ability['name'], 'only_owned' => (bool) $ability['only_owned'] ];

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

    public function easyStore (Request $request) : JsonResponse
    {
        $rules = [
            'email' => 'required|email|unique:users'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        /** @var User $user */
        $user = new User();

        $temporaryPassword = Utility::getRandomString();

        $user->status = UserStatus::EMAIL_VERIFICATION_NEEDED;
        $user->email = $input['email'];
        $user->password = Hash::make($temporaryPassword);
        $user->node_key = Utility::getRandomString(2);

        $user->saveOrFail();

        $resetToken = PasswordResetToken::create([
                                                     'token' => Utility::getRandomString(1),
                                                     'user_id' => $user->id,
                                                     'ip' => $request->ip(),
                                                     'expires' => Carbon::now()->addDays(env('EASY_SIGNUP_TOKEN_EXPIRY_IN_DAYS', 10))
                                                 ]);

        $this->afterCreation($user, [ 'easy' => true, 'resetToken' => $resetToken->token ]);

        UserMeta::addOrUpdateMeta($user, UserMetaKeys::SourcedFromEasySignup, true);

        $issuedToken = $user->createToken("Direct issuance based on easy signup.");

        $data = [
            'user' => $user->toArray(),
            'auth' => [
                'accessToken' => $issuedToken->accessToken,
                'expiry' => env('TOKEN_EXPIRY', 10) * 60
            ]
        ];

        return $this->respond($data);
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
            UserMetaKeys::TaxIdentification => 'sometimes|min:1|max:96',
            UserMetaKeys::ShowSplashScreen => 'sometimes|trueboolean'
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

        $this->afterCreation($user);

        return $this->respond($user->toArray(), [], Messages::USER_CREATED, ResponseType::CREATED);
    }

    private function afterCreation (User $user, array $eventBag = [])
    {
        UserMeta::addOrUpdateMeta($user, UserMetaKeys::ShowSplashScreen, true);
        UserMeta::addOrUpdateMeta($user, UserMetaKeys::LoginCount, 0);

        PermissionManager::assign($user, UserRoles::USER);

        event(new UserEvent(Events::USER_CREATED, $user, $eventBag));
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
            'name' => 'sometimes|min:1|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|min:5|max:72',
            'current_password' => 'required_with:password|min:5|max:72',
            UserMetaKeys::AddressLineOne => 'sometimes|min:1|max:255',
            UserMetaKeys::AddressLineTwo => 'sometimes|min:1|max:255',
            UserMetaKeys::City => 'sometimes|min:1|max:64',
            UserMetaKeys::State => 'sometimes|min:1|max:64',
            UserMetaKeys::PostCode => 'sometimes|min:1|max:64',
            UserMetaKeys::Country => 'sometimes|country|max:64',
            UserMetaKeys::PhoneNumber => 'sometimes|min:1|max:64',
            UserMetaKeys::Organization => 'sometimes|min:1|max:64',
            UserMetaKeys::TaxIdentification => 'sometimes|min:1|max:96',
            UserMetaKeys::ShowSplashScreen => 'sometimes|boolean'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        if (isset($input['name']))
            $user->name = $input['name'];

        if (isset($input['email']) && $user->email != $input['email'])
        {
            // User is attempting to change his email address
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::OldEmailAddress, $user->email);
            $user->email = $input['email'];
        }

        if (isset($input['password']))
        {
            if (! Hash::check($input['current_password'], $user->password))
                throw new UserFriendlyException(Errors::CURRENT_PASSWORD_MISMATCH, ResponseType::FORBIDDEN);

            $user->password = Hash::make($input['password']);

            event(new UserEvent(Events::USER_PASSWORD_UPDATED, $user, [
                'ip' => $request->ip()
            ]));
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
        $user->status = UserStatus::DISABLED;

        // User cannot actually be removed without major consequences elsewhere.
        // Thus, we simply disable it.
        $user->saveOrFail();

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
            $user = User::findByEmail($email)->firstOrFail();

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
                // This check prevents against you verifying an email you actually do not own.
                if ($verifyToken !== $token || $parsedData['email'] !== $user->email)
                    throw new UserFriendlyException(Errors::USER_VERIFICATION_FAILED, ResponseType::FORBIDDEN);

                $user->status = UserStatus::ACTIVE;
                $user->saveOrFail();

                UserMeta::deleteMeta($user, UserMetaKeys::VerifyToken);

                return $this->respond(null, [], Messages::USER_VERIFIED, ResponseType::OK);
            }
            else
                throw new UserFriendlyException(Errors::USER_ALREADY_VERIFIED);
        }

        throw new UserFriendlyException(Errors::USER_VERIFICATION_FAILED, ResponseType::FORBIDDEN);
    }

    public function regenNodeKey(Request $request)
    {
        $user = $request->user();

        $user->node_key = Utility::getRandomString(2);
        $user->saveOrFail();

        return $this->respond($user->toArray(), [], Messages::NODE_KEY_REGENERATED);
    }

}
