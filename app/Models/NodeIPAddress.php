<?php

namespace App;

class NodeIPAddress extends BaseModel
{
    protected $table = 'node_ip_addresses';
    protected $hidden = [ 'node_id', 'updated_at' ];

    public function node ()
    {
        return $this->belongsTo(Node::class);
    }
}
