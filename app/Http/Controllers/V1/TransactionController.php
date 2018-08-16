<?php


namespace App\Http\Controllers\V1;


use App\Constants\Events;
use App\Constants\PaymentProcessor;
use App\Constants\PaymentType;
use App\Constants\TransactionReasons;
use App\Events\BillingEvent;
use App\Invoice;
use App\Libraries\PaginationManager;
use App\Libraries\SearchManager;
use App\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends CRUDController
{
    /*
     * This controller is INTENTIONALLY missing destroy/update
     * Financial accounting does not support either action, neither do we.
     * If a reversal is needed, an extra transaction needs to be added.
     * This controller solely exists to allow admins to add in missing payments / search for them, that's IT.
     */
    public function __construct()
    {
        $this->resource = 'transaction';
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeResource();

        $rules = [
            'invoice_id' => 'required|numeric|exists:invoices,id',
            'payment_processor' => Rule::in(PaymentProcessor::getConstants()),
            'reference' => 'required|alpha_dash',
            'type' => Rule::in(PaymentType::getConstants()),
            'reason' => Rule::in(TransactionReasons::getConstants()),
            'amount' => 'required|numeric',
            'fee' => 'sometimes|numeric',
        ];

        $this->validate($request, $rules);
        $input = $this->cherryPick($request, $rules);

        $invoice = Invoice::findOrfail($input['invoice_id']);

        $transaction = new Transaction();
        foreach ($input as $key => $value)
        {
            $transaction->{$key} = $value;
        }

        $transaction->currency = $invoice->currency;
        $transaction->saveOrFail();

        event(new BillingEvent(Events::BILLING_TRANSACTION_ADDED, $transaction));

        return $this->respond($transaction->toArray());
    }

    public function show(Request $request, int $id, String $action = null): JsonResponse
    {
        $transaction = Transaction::findOrFail($id);
        $this->authorizeResource($transaction);

        return $this->respond($transaction->toArray());
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeResource();

        $rules = [
            'searchId' => 'sometimes|alphanum'
        ];
        $this->validate($request, $rules);

        $queryBuilder = SearchManager::process($request, $this->resource);
        return PaginationManager::paginate($request, $queryBuilder);
    }
}