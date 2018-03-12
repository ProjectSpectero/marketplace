<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\PaymentType;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\Libraries\BillingUtils;
use App\Libraries\PaginationManager;
use App\Libraries\SearchManager;
use App\Transaction;
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

    public function index(Request $request): JsonResponse
    {
        $this->authorizeResource();

        $rules = [
            'searchId' => 'sometimes|alphanum'
        ];

        $this->validate($request, $rules);

        $queryBuilder = SearchManager::process($request, 'invoice');

        return PaginationManager::paginate($request, $queryBuilder);
    }


    public function store(Request $request): JsonResponse
    {
        $this->authorizeResource();

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
        $input = $this->cherryPick($request, $rules);

        $invoice = new Invoice();
        $invoice->order_id = $input['order_id'];
        $invoice->user_id = $input['user_id'];
        $invoice->amount = $input['amount'];
        $invoice->tax = $input['tax'];
        $invoice->status = $input['status'];
        $invoice->due_date = $input['due_date'];

        if (isset($input['currency']))
        {
            $currency = $input['currency'];
            $invoice->currency = isset($currency) ? $currency : $invoice->currency;
        }

        $invoice->saveOrFail();

        return $this->respond($invoice->toArray(), [], Messages::INVOICE_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
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
        $input = $this->cherryPick($request, $rules);

        $invoice = Invoice::findOrFail($id);

        $this->authorizeResource($invoice);

        foreach ($input as $key => $value)
            $invoice->$key = $value;

        $invoice->saveOrFail();

        return $this->respond($invoice->toArray(), [], Messages::INVOICE_UPDATED);
    }

    public function self(Request $request)
    {
        $user = $request->user();
        return PaginationManager::paginate($request, Invoice::findForUser($user->id));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $this->authorizeResource($invoice);

        $invoice->delete();

        return $this->respond(null, [], Messages::INVOICE_DELETED, ResponseType::NO_CONTENT);
    }

    public function show (Request $request, int $id, String $action = null) : JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $this->authorizeResource($invoice);

        switch ($action)
        {
            case 'transactions':
                return PaginationManager::paginate($request, Transaction::findForInvoice($invoice)->noEagerLoads());
            case 'due':
                $amount = BillingUtils::getInvoiceDueAmount($invoice);

                if ($amount < 0)
                    $amount = 0;

                return $this->respond([ 'amount' => $amount, 'currency' => $invoice->currency ]);
            default:
                return $this->respond($invoice->toArray());
        }
    }

    public function render (Request $request, int $id)
    {
        $invoice = Invoice::findOrFail($id);

        $this->authorizeResource($invoice, 'invoice.render');

        return $this->renderInvoice($invoice);
    }

    private function getMetaValueIfNotNull (Model $model)
    {
        if ($model != null)
            return $model->meta_value;

        return '';
    }
}
