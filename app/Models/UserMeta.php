<?php

namespace App;

use App\Constants\UserMetaKeys;
use Illuminate\Database\Eloquent\Model;
use App\Libraries\Utility;
use App\Traits\MetaTrait;

class UserMeta extends Model
{

    use MetaTrait;

    protected $fillable = [ 'user_id', 'meta_key', 'meta_value', 'value_type' ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    static function getUserPublicMeta(User $user)
    {
        $userMeta = array();
        foreach (UserMetaKeys::getPublicMetaKeys() as $key)
        {
            if ($meta = self::loadMeta($user, $key))
                $userMeta[$key] = $meta->meta_value;
        }
        return $userMeta;
    }
}
