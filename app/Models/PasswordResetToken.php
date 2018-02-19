<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends BaseModel
{

    protected $fillable = ['token', 'user_id', 'ip', 'expires'];

    public function user ()
    {
        return $this->belongsTo(User::class);
    }
}
