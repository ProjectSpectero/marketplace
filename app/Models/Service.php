<?php

namespace App;

class Service extends BaseModel
{
    protected $hidden = [ 'created_at', 'updated_at', 'config', 'connection_resource', 'node_id' ];
    protected $casts = [ 'connection_resource' => 'array', 'config' => 'array' ];

    public function node ()
    {
        return $this->belongsTo(Node::class);
    }

    public function getConnectionResourceAttribute ($value)
    {
        return json_decode($value, true);
    }
}
