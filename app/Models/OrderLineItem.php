<?php

namespace App;

use App\Constants\OrderResourceType;
use App\Errors\FatalException;

class OrderLineItem extends BaseModel
{
    protected $casts = [ 'amount' => 'float', 'resource' => 'integer' ];
    protected $hidden = [ 'updated_at' ];

    public function order ()
    {
        return $this->belongsTo(Order::class);
    }

    public function enterpriseResource ()
    {
        if ($this->type !== OrderResourceType::ENTERPRISE)
            throw new FatalException("Resource is not of the enterprise type, invalid invocation.");

        return $this->hasMany(EnterpriseResource::class, 'order_line_item_id');
    }
}
