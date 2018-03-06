<?php

namespace App;

class Service extends BaseModel
{
    protected $hidden = [ 'updated_at', 'config', 'connection_resource' ];

    public function node ()
    {
        return $this->belongsTo(Node::class);
    }
}
