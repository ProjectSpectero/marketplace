<?php

namespace App;

class NodeGroup extends BaseModel
{

    protected $with = ['nodes'];

    public function getActiveOrders(Node $node)
    {
        return \DB::table('orders')
            ->join('order_line_items', 'orders.id', '=', 'order_line_items.id')
            ->select('orders.*', 'order_line_items.*')
            ->where([
                ['status', '=', 'active'],
                ['order_line_items.type', '=', 'NODE'],
                ['order_line_items.resource', '=', (string) $node->id]
            ])
            ->get();
    }

    public function nodes ()
    {
        return $this->hasMany(Node::class, 'group_id');
    }
}
