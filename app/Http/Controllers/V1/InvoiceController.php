<?php

namespace App\Http\Controllers\V1;

use App\Constants\Currency;
use App\Constants\Errors;
use App\Constants\InvoiceStatus;
use App\Constants\InvoiceType;
use App\Constants\Messages;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\Libraries\BillingUtils;
use App\Libraries\PaginationManager;
use App\Libraries\SearchManager;
use App\Libraries\Utility;
use App\Mail\NewInvoiceGeneratedMail;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

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
            'order_id' => 'required|integer|exists:orders,id',
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric',
            'tax' => 'required|numeric',
            'currency' => [ 'sometimes', Rule::in(Currency::getConstants()) ],
            'status' => [ 'required'. Rule::in(InvoiceStatus::getConstants()) ],
            'due_date' => 'required|date'
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
        $invoice->currency = $input['currency'] ?? Currency::USD;

        $invoice->saveOrFail();

        return $this->respond($invoice->toArray(), [], Messages::INVOICE_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $this->authorizeResource($invoice);

        $rules = [
            'order_id' => 'required|integer|exists:orders,id',
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric',
            'tax' => 'required|numeric',
            'currency' => [ 'sometimes', Rule::in(Currency::getConstants()) ],
            'status' => [ 'required'. Rule::in(InvoiceStatus::getConstants()) ],
            'due_date' => 'required|date'
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        foreach ($input as $key => $value)
            $invoice->$key = $value;

        $invoice->saveOrFail();

        return $this->respond($invoice->toArray(), [], Messages::INVOICE_UPDATED);
    }

    public function self(Request $request)
    {
        $rules = [
            'searchId' => 'sometimes|alphanum'
        ];
        $this->validate($request, $rules);

        $user = $request->user();
        $queryBuilder = SearchManager::process($request, 'invoice', Invoice::findForUser($user->id));
        return PaginationManager::paginate($request, $queryBuilder);
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
        /** @var Invoice $invoice */
        $invoice = Invoice::findOrFail($id);
        $this->authorizeResource($invoice);

        $dataHolder = [];

        switch ($action)
        {
            case 'auto':
                if (! $invoice->isPayable())
                    throw new UserFriendlyException(Errors::INVOICE_STATUS_MISMATCH);

                $method = BillingUtils::resolveAutoDeductionMethod($invoice);
                $dataHolder = [
                    'possible' => $method != null,
                    'method' => $method
                ];

                break;

            case 'due':
                $amount = BillingUtils::getInvoiceDueAmount($invoice);
                if ($amount < 0)
                    $amount = 0;

                $dataHolder = [ 'amount' => $amount, 'currency' => $invoice->currency ];
                break;

            case 'gateways':
                $dataHolder = BillingUtils::resolveUsableGateways($invoice, $request->user());
                break;

            case 'transactions':
                return PaginationManager::paginate($request, Transaction::findForInvoice($invoice)->noEagerLoads());

            default:
                $dataHolder = $invoice->toArray();
        }

        return $this->respond($dataHolder);
    }

    public function generateCreditInvoice (Request $request) : JsonResponse
    {
        $rules = [
            'amount' => 'required|numeric|min:' . env('LOWEST_ALLOWED_PAYMENT', 5) . '|max:' . env('CREDIT_ADD_LIMIT', 100)
        ];
        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);
        $user = $request->user();

        if ($user->credit + $input['amount'] > env('CREDIT_ADD_LIMIT', 100))
            throw new UserFriendlyException(Errors::CREDIT_LIMIT_EXCEEDED);

        $creditInvoices = Invoice::findForUser($user->id)
            ->where('type', InvoiceType::CREDIT)
            ->where('status', InvoiceStatus::UNPAID)
            ->get();

        if (! $creditInvoices->isEmpty())
            throw new UserFriendlyException(Errors::UNPAID_CREDIT_INVOICES_ARE_PRESENT, ResponseType::FORBIDDEN);

        $invoice = new Invoice();
        $invoice->user_id = $user->id;
        $invoice->amount = $input['amount'];
        $invoice->currency = Currency::USD; // TODO: Eventually make this use the user's saved currency identifier
        $invoice->type = InvoiceType::CREDIT;
        $invoice->status = InvoiceStatus::UNPAID;
        $invoice->due_date = Carbon::now();
        $invoice->saveOrFail();

        Mail::to($user->email)->queue(new NewInvoiceGeneratedMail($invoice));

        return $this->respond($invoice->toArray());
    }

    public function getMaxCredit(Request $request)
    {
        $user = $request->user();
        $creditLimit = env('CREDIT_ADD_LIMIT', 100);
        $leftOverLimit = $creditLimit - $user->credit;
        return $this->respond([
            'credit_limit' => (int) $creditLimit,
            'can_add' => $leftOverLimit,
            'currency' => $user->credit_currency
        ]);
    }
}
