<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceIPAddress extends BaseModel
{
    public function service ()
    {
        return $this->belongsTo(Service::class);
    }
}
