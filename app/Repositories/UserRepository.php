<?php


namespace App\Repositories;


use App\User;
use GuzzleHttp\Client;

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
     * Creates a user with hashed password
     *
     * @param array $input
     */

    public function userCreate(array $input, $validator)
    {
        if (isset($input['password'])) {
            $input['password'] = \Illuminate\Support\Facades\Hash::make($input['password']);
        }

        if ($validator->fails()) {
            $result = 'Error creating user';
        } else {
            $result = User::create($input);
        }

        return $result;
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

        $response = $http->post('http://homestead.marketplace/oauth/token', [
            'form_params' => [
                'grant_type' => $grantType,
                'client_id' => env('PASSWORD_CLIENT_ID'),
                'client_secret' => env('PASSWORD_CLIENT_SECRET'),
                'username' => $data['username'],
                'password' => $data['password'],
            ],
        ]);

        $data = json_decode($response->getBody());

        return [
            'access_token' => $data->access_token,
            'expires_in' => $data->expires_in
        ];
    }
}
