<?php


namespace App\Repositories;


use App\User;
use App\BackupCode;
use App\Repositories\UserMetaRepository;
use App\Constants\UserMetaKeys;
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
     * Generate a Google2FA secret key and backup codes
     *
     */

    public function generateSecretKey($user)
    {
        $google2fa = new Google2FA();
        $secretKey = $google2fa->generateSecretKey();
        $errors = array();

        if (empty($user->backupCodes->all())) { 
            // Generate 5 backup codes
            for ($i = 0; $i < 5; $i++) {
                BackupCode::create([
                    'user_id' => $user->id,
                    'code' => md5(uniqid(rand(), true))
                ]);
            }
        } else {
            $errors = array(
                'BACKUP_ALREADY_PRESENT' => 'You already have backup codes'
            );
        }
    
        UserMetaRepository::addMeta($user, UserMetaKeys::SecretKey, $secretKey);
        
        $google2fa_url = $google2fa->getQRCodeGoogleUrl(
            env('COMPANY_NAME'),
            $user->email,
            \App\UserMeta::loadMeta($user, UserMetaKeys::SecretKey)
        );        

        return [
            'errors' => $errors,
            'secret_key' => $secretKey,
            'qr_code' => $google2fa_url,
            'backup_codes' => BackupCode::where('user_id', $user->id)->pluck('code')
        ];
    }

    /**
     * Veirify the user with Google2FA
     *
     */

    public function verifyUser($user, $secret)
    {
        $google2fa = new Google2FA();

        $backupCodes = $user->backupCodes;

        foreach ($backupCodes as $code) {
            if ($secret == $code->code) {
                $code->delete();
                return true;
            }
        }
        
        $valid = $google2fa->verifyKey(
          \App\UserMeta::loadMeta($user, UserMetaKeys::SecretKey)->first()->meta_value, $secret
        );
        
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
