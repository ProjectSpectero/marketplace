<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends BaseModel
{

    protected $with = ['lineItems', 'lastInvoice'];
    protected $hidden = [ 'notes' ];

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
