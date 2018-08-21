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

    public static function findByToken (string $token)
    {
        return static::where('token', $token)
            ->whereRaw('expires > NOW()');
    }

    public static function deleteForUser (User $user)
    {
        return static::where('user_id', $user->id)->delete();
    }
}
