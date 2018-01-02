<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserMeta extends Model
{

    protected $fillable = ['user_id', 'meta_key', 'meta_value'];

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

    public static function addOrUpdateMeta ($user, $key, $value)
    {
        if (!empty(static::loadMeta($user, $key)->all())) {
            $userMeta = UserMeta::loadMeta($user, $key)->first();
            $userMeta->meta_value = $value;
            $userMeta->save();
            return;
        }

        static::create([
            'user_id' => $user->id,
            'meta_key' => $key,
            'meta_value' => $value
        ]);
    }

    public static function deleteMeta (User $user, String $key)
    {
        $userMeta = static::loadMeta($user, $key)->first();
        if (! empty($userMeta))
            $userMeta->delete();
    }
}
