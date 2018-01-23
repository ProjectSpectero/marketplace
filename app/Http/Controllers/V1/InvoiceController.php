<?php

namespace App\Http\Controllers\V1;

use App\Invoice;
use \PDF;

class InvoiceController
{
    public function show($id, String $action)
    {
        $invoice = Invoice::findOrFail($id);

        $pdf = PDF::loadView('invoice', ['invoice' => $invoice]);
        return $pdf->$action('invoice.pdf');
    }
}
