<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    public function user ()
    {
        return $this->hasOne(User::class);
    }
}
