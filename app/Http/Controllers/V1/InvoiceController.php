<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\UserMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use \PDF;

class InvoiceController extends CRUDController
{
    public function __construct()
    {
        $this->resource = 'invoice';
    }

    public function render (Request $request, int $id)
    {
        $invoice = Invoice::findOrFail($id);
        $user = $invoice->order->user;

        try
        {
            $addrLine1 = UserMeta::loadMeta($user, UserMetaKeys::AddressLineOne, true)->meta_value;
            $addrLine2 = UserMeta::loadMeta($user, UserMetaKeys::AddressLineTwo, true)->meta_value;
            $city = UserMeta::loadMeta($user, UserMetaKeys::City, true)->meta_value;
            $state = UserMeta::loadMeta($user, UserMetaKeys::State, true)->meta_value;
            $country = UserMeta::loadMeta($user, UserMetaKeys::Country, true)->meta_value;
            $postCode = UserMeta::loadMeta($user, UserMetaKeys::PostCode, true)->meta_value;

            // These are nullable
            $organization = UserMeta::loadMeta($user, UserMetaKeys::Organization);
            $taxId = UserMeta::loadMeta($user, UserMetaKeys::TaxIdentification);

        }
        catch (ModelNotFoundException $e)
        {
            throw new UserFriendlyException(Errors::BILLING_PROFILE_INCOMPLETE, ResponseType::FORBIDDEN);
        }

        $organization = $this->getMetaValueIfNotNull($organization);
        $taxId = $this->getMetaValueIfNotNull($taxId);

        $formattedUserAddress = $addrLine1;
        if (! empty($addrLine2))
            $formattedUserAddress .= PHP_EOL . $addrLine2;
        $formattedUserAddress .= PHP_EOL . $city . ', ' . $state . ', ' . $postCode;
        $formattedUserAddress .= PHP_EOL . $country;

//        $this->authorizeResource($invoice, 'invoice.pdf');
        return View::make('invoice', [
            'invoice' => $invoice,
            'lineItems' => $invoice->order->lineItems,
            'taxId' => $taxId,
            'userAddress' => $formattedUserAddress,
            'organization' => $organization,
            'transactions' => $invoice->transactions,
        ]);
    }

    private function getMetaValueIfNotNull (Model $model)
    {
        if ($model != null)
            return $model->meta_value;

        return '';
    }
}
