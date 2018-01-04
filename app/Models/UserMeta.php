<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Libraries\Utility;

class UserMeta extends Model
{

    protected $fillable = ['user_id', 'meta_key', 'meta_value'];

    /**
     * Custom scope that returns User meta_value by key
     *
     * @param Model $user
     * @param string $key
     * @param bool $throwsException
     *
     * @return Collection UserMeta
     */

    public function scopeLoadMeta($query, User $user, $key = '', $throwsException = false)
    {
        if (empty($key))
            return $user->userMeta;

        $constraint = $query->where(['user_id' => $user->id, 'meta_key' => $key]);
        return $throwsException ? $constraint->firstOrFail() : $constraint->first();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getValue()
    {
        $value = $this->meta_value;
        if (in_array($this->value_type, Utility::$metaDataTypes))
            settype($value, $this->value_type);

        return $value;
    }

    public static function addOrUpdateMeta (User $user, String $key, $value) : UserMeta
    {
        $type = gettype($value);
        $type = in_array($type, Utility::$metaDataTypes) ? $type : 'string';

        if (!empty(static::loadMeta($user, $key)->all()))
        {
            $userMeta = UserMeta::loadMeta($user, $key)->first();
            $userMeta->meta_value = $value;
            $userMeta->save();
            return $userMeta;
        }

        return static::create([
            'user_id' => $user->id,
            'meta_key' => $key,
            'value_type' => $type,
            'meta_value' => $value
        ]);
    }

    public static function deleteMeta (User $user, String $key)
    {
        $userMeta = static::loadMeta($user, $key);
        if (! empty($userMeta))
            $userMeta->delete();
    }
}
