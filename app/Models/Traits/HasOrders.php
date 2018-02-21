<?php


namespace App\Models\Traits;


use App\Constants\OrderResourceType;
use Illuminate\Database\Eloquent\Model;

trait HasOrders
{
    /**
     * @param Model $model
     * @param String|null $status
     * @param String $resourceType
     * @return \Illuminate\Support\Collection
     */
    public function genericGetOrders(Model $model, String $status = null, String $resourceType = OrderResourceType::NODE)
    {
        $constraints = [];

        if ($status != null)
            $constraints[] = [ 'status', $status ];

        $constraints[] = ['order_line_items.type', OrderResourceType::NODE];
        $constraints[] = ['order_line_items.resource', $model->id];

        return \DB::table('orders')
            ->join('order_line_items', 'orders.id', '=', 'order_line_items.id')
            ->select('orders.*', 'order_line_items.*')
            ->where($constraints)
            ->get();
    }
}