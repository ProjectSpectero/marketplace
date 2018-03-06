<?php

namespace App;

class Service extends BaseModel
{
    protected $hidden = [ 'updated_at' ];

    public function node ()
    {
        return $this->belongsTo(Node::class);
    }
}
