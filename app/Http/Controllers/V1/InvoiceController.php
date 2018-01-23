<?php

namespace App\Http\Controllers\V1;

use \PDF;

class InvoiceController
{
    public function download()
    {
        $pdf = PDF::loadView('invoice');
        return $pdf->download('invoice.pdf');
    }

    public function show()
    {
        $pdf = PDF::loadView('invoice');
        return $pdf->stream();
    }
}
