<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    public function node ()
    {
        return $this->hasOne(Node::class);
    }
}
