<?php

namespace App;

use App\Constants\Currency;
use App\Constants\InvoiceStatus;
use Carbon\Carbon;

class Invoice extends BaseModel
{
    protected $casts = [ 'amount' => 'float', 'tax' => 'float' ];
    //protected $with = [ 'transactions' ];
    protected $hidden = [ 'notes', 'user_id' ];
    protected $dates = [
        'created_at',
        'updated_at',
        'due_date',
        'last_reminder_sent'
    ];

    public $searchAble = [ 'id', 'status', 'currency', 'amount', 'due_date', 'order_id' ];


    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function findForOrder(Order $order)
    {
        return static::where('order_id', $order->id);
    }

    public function isPayable () : bool
    {
        return in_array($this->status, InvoiceStatus::getPayable());
    }

}
