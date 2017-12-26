<?php


namespace App\Repositories;


use App\User;
use App\UserMeta;
use App\BackupCode;
use App\Repositories\UserMetaRepository;
use App\Constants\UserMetaKeys;
use App\Constants\Errors;
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
        $secretKey = UserMeta::loadMeta($user, UserMetaKeys::SecretKey);
        $errors = array();
        $backupCodes = new BackupCode();
        if (empty($user->backupCodes->all())) { 
            // Generate 5 backup codes
            $backupCodes->generateCodes($user); 
        } else {
            $errors = array(
                Errors::BACKUP_CODES_ALREADY_PRESENT                
            );
        }
         
        if (empty($secretKey->first())) {
            $secretKey = $google2fa->generateSecretKey();
            UserMetaRepository::addMeta($user, UserMetaKeys::SecretKey, $secretKey);
        }
        
        $google2fa_url = $google2fa->getQRCodeGoogleUrl(
            env('COMPANY_NAME'),
            $user->email,
            UserMeta::loadMeta($user, UserMetaKeys::SecretKey)
        );        

        return [
            'errors' => $errors,
            'secret_key' => $secretKey->first()->meta_value,
            'qr_code' => $google2fa_url,
            'backup_codes' => BackupCode::where('user_id', $user->id)->pluck('code')
        ];
    }

    /**
     * Invalidate previous backup codes 
     * and generate new ones
     */

    public function regenKeys($user)
    {
        foreach($user->backupCodes as $code) {
            $code->delete(); 
        }

        $backupCodes = new BackupCode();
        $backupCodes->generateCodes($user);

        return [
          'backup_codes' => $user->backupCodes->pluck('code')
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
          UserMeta::loadMeta($user, UserMetaKeys::SecretKey)->first()->meta_value, $secret
        );

        if ($valid && UserMeta::loadMeta($user, UserMetaKeys::hasTfaOn) == 'false') {
            UserMetaRepository::addMeta($user, UserMetaKeys::hasTfaOn, 'true');
        }
        
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
            'email' => $input['email']
        ]);

        $user->password = $input['password'];
        $user->save();

        unset($input['name'], $input['email'], $input['password'], $input['c_password']);
        
        foreach ($input as $key => $value) {
            UserMetaRepository::addMeta($user, $key, $value);
        }

        UserMetaRepository::addMeta($user, UserMetaKeys::hasTfaOn, "false");

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

        $response = $http->post(env('APP_URL') . '/oauth/token', [
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
