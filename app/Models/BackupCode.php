<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BackupCode extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'code'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generateCodes($user)
    {   
        for ($i = 0; $i < 5; $i++) {
            BackupCode::create([
                'user_id' => $user->id,
                'code' => md5(uniqid(mt_rand(), true))
            ]);
        }
    } 
}
