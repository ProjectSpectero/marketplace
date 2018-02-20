<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invoice extends BaseModel
{
    protected $casts = [ 'amount' => 'float', 'tax' => 'float' ];
    protected $with = [ 'transactions' ];
    protected $hidden = [ 'notes' ];

    public $searchAble = ['status', 'currency', 'amount', 'due_date'];

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
}
