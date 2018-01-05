<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Node extends Model
{
    use SoftDeletes;

    public function nodeMeta()
    {
        return $this->hasMany(NodeMeta::class);
    }
}
