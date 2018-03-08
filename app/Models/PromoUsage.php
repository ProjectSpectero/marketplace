<?php

namespace App;

class PromoUsage extends BaseModel
{
    public function code ()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function user ()
    {
        return $this->belongsTo(User::class);
    }
}
