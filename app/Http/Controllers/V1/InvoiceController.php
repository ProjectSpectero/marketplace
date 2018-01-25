<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use App\Invoice;
use Illuminate\Http\Request;
use \PDF;

class InvoiceController extends CRUDController
{
    public function __construct()
    {
        $this->resource = 'invoice';
    }

    public function pdf(Request $request, int $id, String $action)
    {
        $invoice = Invoice::findOrFail($id);
        $this->authorizeResource($invoice, 'invoice.pdf');
        $pdf = PDF::loadView('invoice', ['invoice' => $invoice]);
        $fileName = env('COMPANY_NAME', 'Spectero') .' Invoice #' . $invoice->id . '.pdf';

        switch ($action)
        {
            case 'view':
                return $pdf->stream($fileName);
            case 'download':
                return $pdf->download($fileName);
            default:
                throw new UserFriendlyException(Errors::UNKNOWN_ACTION);
        }
    }
}
