<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceIPAddress extends BaseModel
{
    protected $table = 'service_ip_address';

    public function service ()
    {
        return $this->belongsTo(Service::class);
    }
}
