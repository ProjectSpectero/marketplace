<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    public static function findForUser (int $id)
    {
        return static::where('user_id', $id);
    }
}
