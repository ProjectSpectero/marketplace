<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Libraries\Utility;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        $resolvedType = gettype($value);
        $type = in_array($resolvedType, Utility::$metaDataTypes) ? $resolvedType : 'string';
        $userMeta = null;

        try
        {
            $userMeta = static::loadMeta($user, $key, true);
            $userMeta->meta_value = $value;
            $userMeta->value_type = $type;
            $userMeta->save();
        }
        catch (ModelNotFoundException $silenced)
        {
            $userMeta = static::create([
                                         'user_id' => $user->id,
                                         'meta_key' => $key,
                                         'value_type' => $type,
                                         'meta_value' => $value
                                     ]);
        }

        return $userMeta;
    }

    public static function deleteMeta (User $user, String $key)
    {
        $userMeta = static::loadMeta($user, $key);
        if (! empty($userMeta))
            $userMeta->delete();
    }
}
