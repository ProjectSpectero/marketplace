<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderLineItem extends BaseModel
{
    protected $casts = ['amount' => 'float', 'resource' => 'integer'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public static function populate(array $items, int $order_id)
    {
        $lineItems = array();
        foreach ($items as $item)
        {
            switch($item['type'])
            {
                case 'NODE':
                    $resource = Node::findOrFail($item['id']);
                    break;
                default:
                    $resource = NodeGroup::findOrFail($item['id']);
            }
            $lineItem = new OrderLineItem();
            $lineItem->description = $resource->friendly_name;
            $lineItem->order_id = $order_id;
            $lineItem->type = $item['type'];
            $lineItem->resource = $item['type'];
            $lineItem->quantity = 1;
            $lineItem->amount = $resource->price;

            $lineItem->saveOrFail();

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }
}
