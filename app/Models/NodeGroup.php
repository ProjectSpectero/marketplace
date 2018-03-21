<?php

namespace App;

use App\Constants\OrderResourceType;
use App\Models\Traits\HasOrders;

class NodeGroup extends BaseModel
{
    use HasOrders;

    protected $with = [ 'nodes' ];
    protected $hidden = [ 'user_id', 'updated_at' ];

    public $searchAble = [
        'friendly_name', 'status', 'model', 'price'
    ];

    public function nodes ()
    {
        return $this->hasMany(Node::class, 'group_id');
    }

    public function getOrders (String $status = null)
    {
        return $this->genericGetOrders($this, $status, OrderResourceType::NODE_GROUP);
    }

    public function getEngagements (String $status = null)
    {
        $query = OrderLineItem::join('node_groups', 'node_groups.id', '=', 'order_line_items.resource')
            ->join('orders', 'orders.id', '=', 'order_line_items.order_id')
            ->where('order_line_items.type', OrderResourceType::NODE_GROUP)
            ->where('order_line_items.resource', $this->id);

        if ($status != null)
            $query->where('orders.status', $status);

        $query->select([ 'order_line_items.*' ]);

        return $query;
    }
}
