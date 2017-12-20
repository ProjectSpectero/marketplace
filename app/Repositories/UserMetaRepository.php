<?php

namespace App\Repositories;

use App\UserMeta;
use App\Constants\UserMetaKeys;

class UserMetaRepository
{
    static function addMeta($user, $key, $value = null)
    {
        if (!empty(UserMeta::loadMeta($user, $key)->all())) {
          $userMeta = UserMeta::loadMeta($user, $key)->first();
          $userMeta->meta_value = $value;
          $userMeta->save();
        }        
        
        UserMeta::create([
            'user_id' => $user->id,
            'meta_key' => $key,
            'meta_value' => $value
        ]);
    }
}
