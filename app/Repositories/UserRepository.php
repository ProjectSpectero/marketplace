<?php


namespace App\Repositories;


use App\User;
use App\UserMeta;
use App\BackupCode;
use App\Constants\UserMetaKeys;
use App\Constants\Errors;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class UserRepository
{


    /**
     * Creates a user with hashed password
     *
     * @param array $input
     * @param Validator $validator
     */

    public function userCreate(array $input)
    {
        if (isset($input['password'])) {
            $input['password'] = Hash::make($input['password']);
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

        return $user;
    }



}
