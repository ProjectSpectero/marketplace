<?php

namespace App;

use App\Constants\OrderResourceType;
use App\Constants\OrderStatus;

class Order extends BaseModel
{
    protected $with = [ 'lineItems', 'lastInvoice' ];
    protected $hidden = [ 'notes', 'user_id', 'subscription_reference', 'subscription_provider', 'accessor' ];
    protected $dates = [
        'created_at',
        'updated_at',
        'due_next'
    ];

    public $searchAble = ['due_next', 'status', 'term', 'created_at', 'id'];

    public function lineItems()
    {
        return $this->hasMany(OrderLineItem::class);
    }

    public function lastInvoice()
    {
        return $this->hasOne(Invoice::class, 'id', 'last_invoice_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isEnterprise () : bool
    {
        foreach ($this->lineItems as $lineItem)
        {
            if ($lineItem->status == OrderStatus::ACTIVE
            && $lineItem->type == OrderResourceType::ENTERPRISE)
                return true;
        }

        return false;
    }
}
