<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PartialAuth extends BaseModel
{
    protected $table = 'partial_auth';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}