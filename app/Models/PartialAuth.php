<?php

namespace App;

class PartialAuth extends BaseModel
{
    protected $table = 'partial_auth';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function findByUserIdAndToken (int $userId, string $token)
    {
        return static::where('user_id', $userId)
            ->where('two_factor_token', $token)
            ->whereRaw('expires > NOW()');
    }
}