<?php

namespace App\Http\Controllers\V1;

use App\Constants\Authority;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Constants\UserRoles;
use App\Constants\UserStatus;
use App\Errors\UserFriendlyException;
use App\Libraries\Utility;
use App\Models\Opaque\OAuthResponse;
use App\Models\Opaque\TwoFactorResponse;
use App\PartialAuth;
use App\Constants\Errors;
use App\User;
use App\UserMeta;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Constants\Messages;

class AuthController extends V1Controller
{
    public function auth(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|email',
            'password' => 'required|min:5|max:72'
        ]);

        $email = $request->get('username');
        $password = $request->get('password');

        $oauthResponse = $this->proxy('password', [
            'username' => $email,
            'password' => $password
        ]);

        if ($oauthResponse->success)
        {
            // FirstOrFail not needed, oAuth succeeded, this user exists.
            $user = User::where('email', $email)->first();

            $error = Utility::resolveStatusError($user);
            if (! empty($error))
                throw new UserFriendlyException($error, ResponseType::FORBIDDEN);

            try
            {
                // Simple existence check, we do not care about the values. That is not our responsibility.
                UserMeta::loadMeta($user, UserMetaKeys::TwoFactorEnabled, true);
                UserMeta::loadMeta($user, UserMetaKeys::TwoFactorSecretKey, true);
            }
            catch (ModelNotFoundException $silenced)
            {
                // Not this user's first time logging in anymore.
                Utility::incrementLoginCount($user);

                // User either doesn't have two factor turned on, or user's secret key somehow (!) doesn't exist
                // Return response as normal
                return $this->respond($oauthResponse->toArray(), [], Messages::OAUTH_TOKEN_ISSUED);
            }

            // At this stage, user has Two Factor auth turned on. Let us create a partial auth entry
            $partialAuthToken = Utility::getRandomString();

            $partialAuth = new PartialAuth();
            $partialAuth->user_id = $user->id;
            $partialAuth->data = $oauthResponse->toJson();
            $partialAuth->two_factor_token = $partialAuthToken;
            $partialAuth->expires = Carbon::now()->addMinute(env('TOKEN_EXPIRY', 10)); // Entry expires at the same time the generated auth token does

            // Exception deliberately not handled, it'll flow up to the error handler if something doesn't work to create a OBJECT_PERSIST_ERROR
            $partialAuth->saveOrFail();

            $twoFactorResponse = new TwoFactorResponse();
            $twoFactorResponse->twoFactorToken = $partialAuthToken;
            $twoFactorResponse->userId = $user->id;

            return $this->respond($twoFactorResponse->toArray(), [], Messages::MULTI_FACTOR_VERIFICATION_NEEDED);
        }
        else
            throw new UserFriendlyException(Errors::AUTHENTICATION_FAILED, ResponseType::FORBIDDEN);
    }

    public function impersonate(Request $request, int $id)
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        /** @var User $requestingUser */
        $requestingUser = $request->user();
        $ip = $request->ip();

        if ($user->id == $requestingUser->id)
            throw new UserFriendlyException(Messages::CANNOT_IMPERSONATE_YOURSELF);

        if ($user->isAn(UserRoles::ADMIN))
            throw new UserFriendlyException(Messages::CANNOT_IMPERSONATE_ADMINS, ResponseType::FORBIDDEN);

        $this->authorizeResource($user, Authority::ImpersonateUsers);

        $key = 'impersonation.tokens.' . $user->id;

        if (\Cache::has($key))
            $ret = \Cache::get($key);
        else
        {
            $issuedToken = $user->createToken("Impersonated by $requestingUser->email #($requestingUser->id) from $ip");
            $ret = new OAuthResponse();

            $ret->accessToken = $issuedToken->accessToken;
            $ret->expiry = env('TOKEN_EXPIRY', 10) * 60;
            $ret->success = true;

            \Cache::put($key, $ret, env('TOKEN_EXPIRY', 10));

            \Log::warning("Impersonation token issued on behalf of $requestingUser->email (ip: $ip) for $user->email (#$user->id)");
        }

        return $this->respond($ret->toArray());
    }


    public function refreshToken(Request $request)
    {

        $refreshToken = $request->get('refresh_token');
        $oauthResponse = $this->proxy('refresh_token', [
                'refresh_token' => $refreshToken
            ]);

        if ($oauthResponse->success)
            return $this->respond($oauthResponse->toArray(), [], Messages::OAUTH_TOKEN_REFRESHED);

        return $this->respond(null, [ Errors::AUTHENTICATION_FAILED ], Errors::REQUEST_FAILED, ResponseType::FORBIDDEN);
    }

    /**
     * Proxy a request to the OAuth server
     *
     * @param string $grantType - what type of grant should be proxied
     * @param array $data - the data to send to the server
     * @return OAuthResponse
     */

    private function proxy(String $grantType, array $data = []) : OAuthResponse
    {
        $http = new Client();
        $ret = new OAuthResponse();

        $oauthType = array(
            'grant_type' => $grantType,
            'client_id' => env('PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSWORD_CLIENT_SECRET')
        );

        $params = array_merge($oauthType, $data);

        $uri = sprintf('%s/%s', env('APP_URL'), 'oauth/token');

        try
        {
            $response = $http->post($uri, [
                'form_params' => $params,
                'connect_timeout' => env('GLOBAL_CONNECT_TIMEOUT_SECONDS', 5)
            ]);
            if ($response->getStatusCode() == 200)
            {
                $data = json_decode($response->getBody());
                $ret->accessToken = $data->access_token;
                $ret->refreshToken = $data->refresh_token;
                $ret->expiry = $data->expires_in;
                $ret->success = true;
            }
        }
        catch (RequestException $requestException)
        {
            // There's no need to spam ourselves with general auth failures, we want to log exceptional cases only.
            $log = false;
            $response = $requestException->getResponse();
            if ($response != null && $response->getStatusCode() != 401)
                $log = true;
            elseif ($response == null)
                $log = true;

            if ($log)
                \Log::error("Request to oauth/token failed!", [ 'ctx' => $requestException ]);

            // API gave something other than 200, assume fail.
            $ret->success = false;
        }

        return $ret;
    }
}
