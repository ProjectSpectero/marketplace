<?php

namespace App;

use App\Constants\UserMetaKeys;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\MetaTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserMeta extends BaseModel
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
            {
                $property = $meta->meta_value;
                settype($property, $meta->value_type);
                $value = $property;
            }
            else
                $value = null;

            $userMeta[$key] = $value;
        }
        return $userMeta;
    }


}
