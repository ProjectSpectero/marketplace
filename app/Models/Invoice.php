<?php

namespace App;

class Invoice extends BaseModel
{
    protected $casts = [ 'amount' => 'float', 'tax' => 'float' ];
    protected $with = [ 'transactions' ];
    protected $hidden = [ 'notes', 'user_id' ];

    public $searchAble = ['status', 'currency', 'amount', 'due_date', 'order_id '];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function user ()
    {
        return $this->belongsTo(User::class);
    }

    public static function findForOrder (Order $order)
    {
        return static::where('order_id', $order->id);
    }
}
