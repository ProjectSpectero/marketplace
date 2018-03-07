<?php

namespace App;

class PromoGroup extends BaseModel
{
    public function code()
    {
        return $this->hasMany(PromoCode::class);
    }
}
