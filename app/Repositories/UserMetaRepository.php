<?php

namespace App\Repositories;

use App\UserMeta;
use App\Constants\UserMetaKeys;

class UserMetaRepository
{
    static function addMeta($user, $key, $value = null)
    {
        UserMeta::create([
            'user_id' => $user->id,
            'meta_key' => $key,
            'meta_value' => $value
        ]);
    }
}
