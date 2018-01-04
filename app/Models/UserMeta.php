<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Libraries\Utility;
use App\Traits\MetaTrait;

class UserMeta extends Model
{

    use MetaTrait;

    protected $fillable = ['user_id', 'meta_key', 'meta_value'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
