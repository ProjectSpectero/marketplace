<?php

namespace App;

use App\Constants\OrderStatus;
use App\Constants\PaymentProcessor;
use App\Libraries\Utility;
use Carbon\Carbon;

class Order extends BaseModel
{

    protected $with = [ 'lineItems', 'lastInvoice' ];
    protected $hidden = [ 'notes', 'user_id', 'subscription_reference', 'subscription_provider' ];

    public $searchAble = ['due_next', 'status', 'term'];

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
}
