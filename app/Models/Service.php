<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Service extends BaseModel
{
    public function node ()
    {
        return $this->hasOne(Node::class);
    }

    public function ipAddresses ()
    {
        return $this->hasMany(ServiceIPAddress::class);
    }
}
