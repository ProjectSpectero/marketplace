<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{

    protected $with = ['lineItems'];

    public function lineItems()
    {
        return $this->hasMany(OrderLineItem::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
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
