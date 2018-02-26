<?php

namespace App;

class Transaction extends BaseModel
{

    protected $casts = ['amount' => 'float', 'fee' => 'float'];
    public $searchAble = [ 'invoice_id' ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public static function findForInvoice (Invoice $invoice)
    {
        return static::where('invoice_id', $invoice->id);
    }
}
