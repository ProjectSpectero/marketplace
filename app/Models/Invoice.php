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

    public static function addNew(int $order_id, int $user_id, array $items)
    {

        $invoice = new Invoice();
        $invoice->order_id = $order_id;
        $invoice->user_id = $user_id;
        $invoice->amount = self::calculateAmount($items);
        $invoice->tax = 12;
        $invoice->status = InvoiceStatus::UNPAID;
        $invoice->due_date = Carbon::now();
        $invoice->currency = Currency::USD;

        $invoice->saveOrFail();

        return $invoice;
    }

    private static function calculateAmount(array $items)
    {
        $amount = 0;
        foreach ($items as $item)
        {
            $amount += $item->amount;
        }

        return $amount;
    }
}
