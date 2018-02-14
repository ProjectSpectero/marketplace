<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{

    protected $with = ['lineItems', 'lastInvoice'];
    protected $hidden = [ 'notes' ];

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

    public static function findForUser (int $id)
    {
        return static::where('user_id', $id);
    }


}
