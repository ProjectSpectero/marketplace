<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public function lineItems()
    {
        return $this->hasMany(OrderLineItems::class);
    }
}
