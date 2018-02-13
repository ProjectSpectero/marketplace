<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $casts = [ 'amount' => 'float', 'tax' => 'float' ];
    protected $with = [ 'transactions' ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public static function findForUser (int $id)
    {
        return static::where('user_id', $id);
    }

    public function user ()
    {
        return $this->belongsTo(User::class);
    }
}
