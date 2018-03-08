<?php

namespace App;

class PromoGroup extends BaseModel
{
    public function codes ()
    {
        return $this->hasMany(PromoCode::class);
    }
}
