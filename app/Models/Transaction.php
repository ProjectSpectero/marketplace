<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends BaseModel
{

    protected $casts = ['amount' => 'float', 'fee' => 'float'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
