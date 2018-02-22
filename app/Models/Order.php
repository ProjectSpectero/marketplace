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

    public static function addNew(User $user, $term)
    {
        $order = new Order();
        $order->user_id = $user->id;
        $order->status = OrderStatus::ACTIVE;
        $order->subscription_reference = Utility::getRandomString(2);
        $order->subscription_provider = PaymentProcessor::STRIPE;
        $order->term = $term;
        $order->due_next = Carbon::now();

        $order->saveOrFail();

        return $order;
    }

}
