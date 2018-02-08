<?php

namespace App;

use App\Constants\UserMetaKeys;
use Illuminate\Database\Eloquent\Model;
use App\Traits\MetaTrait;

class UserMeta extends Model
{

    use MetaTrait;

    protected $fillable = [ 'user_id', 'meta_key', 'meta_value', 'value_type' ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getUserPublicMeta(User $user)
    {
        $userMeta = array();
        foreach (UserMetaKeys::getPublicMetaKeys() as $key)
        {
            $meta = static::loadMeta($user, $key);

            if ($meta instanceof static)
                $value = $meta->meta_value;
            else
                $value = null;

            $userMeta[$key] = $value;
        }
        return $userMeta;
    }
}
