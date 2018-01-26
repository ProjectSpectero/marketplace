<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\UserMeta;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        $user = $invoice->order->user;

        try
        {
            $organization = UserMeta::loadMeta($user, UserMetaKeys::Organization, true)->meta_value;
            $addrLine1 = UserMeta::loadMeta($user, UserMetaKeys::AddressLineOne, true)->meta_value;
            $addrLine2 = UserMeta::loadMeta($user, UserMetaKeys::AddressLineTwo, true)->meta_value;

            $userAddress = $addrLine1 . PHP_EOL . $addrLine2;
        }
        catch (ModelNotFoundException $e)
        {
            $userAddress = '';
            $organization = '';
        }

//        $this->authorizeResource($invoice, 'invoice.pdf');
        $pdf = PDF::loadView('invoice', [
            'invoice' => $invoice,
            'lineItems' => $invoice->order->lineItems,
            'userAddress' => $userAddress,
            'organization' => $organization,
            'transactions' => $invoice->transactions,
        ]);
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
