<?php

namespace App;

class PromoCode extends BaseModel
{

    protected $casts = [
        'amount' => 'float',
        'expires' => 'timestamp'
    ];

    public function group ()
    {
        return $this->belongsTo(PromoGroup::class);
    }

    public function usages ()
    {
        return $this->hasMany(PromoUsage::class);
    }
}
