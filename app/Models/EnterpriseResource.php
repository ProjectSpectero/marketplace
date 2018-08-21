<?php

namespace App;

use App\Constants\OrderResourceType;
use App\Models\Traits\HasOrders;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EnterpriseResource extends Model
{
    use HasOrders;

    public function ip ()
    {
        return $this->belongsTo(NodeIPAddress::class, 'ip_id', 'id');
    }

    public function orderLineItem ()
    {
        return $this->belongsTo(OrderLineItem::class, 'order_line_item_id', 'id');
    }

    public function outgoingIp ()
    {
        return $this->hasOne(NodeIPAddress::class, 'id', 'outgoing_ip_id');
    }

    // TODO: This will run as many queries as there are IPs (all indexed), figure out a better way eventually.
    public function accessor () : string
    {
        return $this->ip->ip . ':' . $this->port;
    }

    public static function findForOrderLineItem (OrderLineItem $lineItem) : Builder
    {
        return static::where('order_line_item_id', $lineItem->id);
    }

    public function getOrders (String $status = null)
    {
        return $this->genericGetOrders($this, $status, OrderResourceType::ENTERPRISE);
    }

}
