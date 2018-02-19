<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NodeGroup extends BaseModel
{
    public function nodes ()
    {
        return $this->hasMany(Node::class, 'group_id');
    }
}
