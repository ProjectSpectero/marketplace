<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceIPAddress extends Model
{
    public function service ()
    {
        return $this->belongsTo(Service::class);
    }
}
