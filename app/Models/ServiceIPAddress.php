<?php

namespace App;

class ServiceIPAddress extends BaseModel
{
    protected $table = 'service_ip_address';
    protected $hidden = [ 'updated_at' ];

    public function service ()
    {
        return $this->belongsTo(Service::class);
    }
}
