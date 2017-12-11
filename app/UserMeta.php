<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserMeta extends Model
{

    /**
     * Custom scope that returns User meta_value by key
     *
     * @param Model $user 
     * @param string $flag
     * @param string $key
     *
     * @return string meta_value
     */

    public function scopeLoadMeta($query, $user, $key = '')
    {
        if (empty($key)) {
            return $user->userMeta;
        }

        return $query->where(['user_id' => $user->id, 'meta_key' => $key])->get();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
