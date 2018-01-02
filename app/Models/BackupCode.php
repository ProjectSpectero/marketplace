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
}
