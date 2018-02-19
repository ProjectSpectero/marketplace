<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NodeGroup extends BaseModel
{

    protected $with = ['nodes'];

    public function nodes ()
    {
        return $this->hasMany(Node::class, 'group_id');
    }
}
