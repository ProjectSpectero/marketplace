<?php

namespace App;

class PasswordResetToken extends BaseModel
{

    protected $fillable = ['token', 'user_id', 'ip', 'expires'];
    protected $hidden = [ 'id', 'ip', 'user_id', 'updated_at' ];

    public function user ()
    {
        return $this->belongsTo(User::class);
    }
}
