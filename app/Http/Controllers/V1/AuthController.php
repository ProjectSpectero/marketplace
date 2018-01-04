<?php

namespace App\Http\Controllers\V1;

use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
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
            'password' => 'required'
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
            
            try
            {
                // Simple existence check, we do not care about the values. That is not our responsibility.
                UserMeta::where(['user_id' => $user->id, 'meta_key' => UserMetaKeys::TwoFactorEnabled])->firstOrFail();
                UserMeta::where(['user_id' => $user->id, 'meta_key' => UserMetaKeys::TwoFactorSecretKey])->firstOrFail();
            }
            catch (ModelNotFoundException $silenced)
            {
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
            return $this->respond(null, [ Errors::AUTHENTICATION_FAILED ], null, ResponseType::FORBIDDEN);
    }


    public function refreshToken(Request $request)
    {
        $refreshToken = $request->get('refresh_token');
        $oauthResponse = $this->proxy('refresh_token', [
                'refresh_token' => $refreshToken
            ]);

        if ($oauthResponse->success)
            return $this->respond($oauthResponse->toArray(), [], Messages::OAUTH_TOKEN_REFRESHED);

        return $this->respond(null, [ Errors::AUTHENTICATION_FAILED ], null, ResponseType::FORBIDDEN);
    }

    /**
     * Proxy a request to the OAuth server
     *
     * @param string $grantType - what type of grant should be proxied
     * @param array $data - the data to send to the server
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
                'form_params' => $params
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
            // API gave something other than 200, assume fail.
            $ret->success = false;
        }

        return $ret;
    }
}
