<?php


namespace App\Repositories;


use App\User;
use App\Repositories\UserMetaRepository;
use GuzzleHttp\Client;
use PragmaRX\Google2FA\Google2FA;

class UserRepository
{
    /**
     * Attempt to create an access token using user credentials
     *
     * @param string $email
     * @param string $password
     */

    public function attemptLogin($email, $password)
    {
        $user = User::where('email', '=', $email)->get();
        $grantType = 'password';

        if (!is_null($user)) {
            return $this->proxy($grantType, [
               'username' => $email,
               'password' => $password
            ]);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
     * Generate a Google2FA secret key and return a url 
     *
     */

    public function generateSecretKey($user)
    {
        $google2fa = new Google2FA();
    
        UserMetaRepository::addMeta($user, 'secret', $google2fa->generateSecretKey());

        $google2fa_url = $google2fa->getQRCodeGoogleUrl(
            env('COMPANY_NAME'),
            $user->email,
            \App\UserMeta::loadMeta($user, 'secret')
        );        

        return $google2fa_url;
    }

    /**
     * Veirify the user with Google2FA
     *
     */

    public function verifyUser($user, $secret)
    {
        $google2fa = new Google2FA();
        
        $valid = $google2fa->verifyKey(\App\UserMeta::loadMeta($user, 'secret')->first()->meta_value, $secret);
        
        return $valid;      
    }

    public function refreshToken($refreshToken)
    {
        $grantType = 'refresh_token';

        return $this->proxy($grantType, [
            'refresh_token' => $refreshToken
        ]);
    }


    /**
     * Creates a user with hashed password
     *
     * @param array $input
     * @param Validator $validator
     */

    public function userCreate(array $input)
    {
        if (isset($input['password'])) {
            $input['password'] = \Illuminate\Support\Facades\Hash::make($input['password']);
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password']
        ]);

        unset($input['name'], $input['email'], $input['password'], $input['c_password']);

        foreach ($input as $key => $value) {
            UserMetaRepository::addMeta($user, $key, $value);
        } 

        return $user;
    }


    /**
     * Proxy a request to the OAuth server
     *
     * @param string $grantType - what type of grant should be proxied
     * @param array $data - the data to send to the server
     */

    public function proxy($grantType, array $data = [])
    {
        $http = new Client();

        $oauthType = array(
            'grant_type' => $grantType,
            'client_id' => env('PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSWORD_CLIENT_SECRET')
        );

        $params = array_merge($oauthType, $data);

        $response = $http->post('http://homestead.marketplace/oauth/token', [
            'form_params' => $params
        ]);

        $data = json_decode($response->getBody());

        return [
            'access_token' => $data->access_token,
            'refresh_token' => $data->refresh_token,
            'expires_in' => $data->expires_in,
            'success' => true
        ];
    }
}
