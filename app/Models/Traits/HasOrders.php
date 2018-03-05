<?php


namespace App\Models\Traits;


use App\Constants\OrderResourceType;
use App\Order;
use Illuminate\Database\Eloquent\Model;

trait HasOrders
{
    /**
     * @param Model $model
     * @param String|null $status
     * @param String $resourceType
     * @return \Illuminate\Database\Query\Builder
     */
    public function genericGetOrders(Model $model, String $status = null, String $resourceType = OrderResourceType::NODE)
    {
        $constraints = [];

        if ($status != null)
            $constraints[] = [ 'orders.status', $status ];

        $constraints[] = [ 'order_line_items.type', $resourceType ];
        $constraints[] = [ 'order_line_items.resource', $model->id ];

        return Order::join('order_line_items', 'order_line_items.order_id', '=', 'orders.id')
            ->select('orders.*')
            ->where($constraints);
    }
}