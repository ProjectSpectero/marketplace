<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    public function nodeMeta()
    {
        return $this->hasMany(NodeMeta::class);
    }
}
