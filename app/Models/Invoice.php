<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $casts = ['amount' => 'float'];

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
}
