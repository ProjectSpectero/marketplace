<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\UserMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rules\In;
use \PDF;

class InvoiceController extends CRUDController
{
    public function __construct()
    {
        $this->resource = 'invoice';
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'order_id' => 'required',
            'user_id' => 'required',
            'amount' => 'required',
            'tax' => 'required',
            'currency' => 'sometimes',
            'status' => 'required',
            'due_date' => 'required'
        ];

        $this->validate($request, $rules);

        $invoice = new Invoice();
        $invoice->order_id = $request->get('order_id');
        $invoice->user_id = $request->get('user_id');
        $invoice->amount = $request->get('amount');
        $invoice->tax = $request->get('tax');
        $invoice->status = $request->get('status');
        $invoice->due_date = $request->get('due_date');

        $currency = $request->get('currency');
        $invoice->currency = isset($currency) ? $currency : $invoice->currency;

        $invoice->saveOrFail();

        return $this->respond($invoice->toArray(), [], Messages::INVOICE_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        foreach ($request as $key => $value)
            $invoice->$key = $value;

        $invoice->saveOrFail();

        $this->validate($request, $id);

        return $this->respond($invoice->toArray(), [], Messages::INVOICE_UPDATED);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $invoice->delete();

        $this->respond(null, [], Messages::INVOICE_DELETED, ResponseType::NO_CONTENT);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        return $this->respond($invoice->toArray());
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
