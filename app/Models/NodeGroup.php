<?php

namespace App;

use App\Constants\OrderResourceType;
use App\Models\Traits\HasOrders;

class NodeGroup extends BaseModel
{
    use HasOrders;

    protected $with = ['nodes'];

    public function nodes ()
    {
        return $this->hasMany(Node::class, 'group_id');
    }

    public function getOrders (String $status = null)
    {
        return $this->genericGetOrders($this, $status, OrderResourceType::NODE_GROUP);
    }
}
