<?php


namespace App\Libraries\Payment;


use App\Constants\Currency;
use App\Constants\Errors;
use App\Constants\PaymentProcessorResponseType;
use App\Constants\PaymentType;
use App\Constants\TransactionReasons;
use App\Errors\NotSupportedException;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\Libraries\BillingUtils;
use App\Libraries\Utility;
use App\Models\Opaque\PaymentProcessorResponse;
use App\Order;
use App\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManualPaymentProcessor extends BasePaymentProcessor
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    function getName(): string
    {
        return "MANUAL";
    }

    function getValidationRules(String $method): array
    {
        switch ($method)
        {
            case "process":
                return
                [
                    'amount' => 'required|numeric|min:0.1',
                    'currency' => [ 'required', Rule::in(Currency::getConstants()) ],
                    'note' => 'sometimes|string|min:5|max:2048',
                ];

            default:
                return [];
        }
    }

    function process(Invoice $invoice): PaymentProcessorResponse
    {
        // This ensures that only admins (or the right people with the right permission) are able to add a manual transaction.
        $this->caller->authorizeResource(null, 'manual.pay');
        $input = $this->caller->cherryPick($this->request, $this->getValidationRules('process'));

        $candidateAmount = $input['amount'];
        $candidateCurrency = $input['currency'];

        if ($invoice->currency !== $candidateCurrency)
            throw new UserFriendlyException(Errors::INVOICE_CURRENCY_MISMATCH);

        $dueAmount = BillingUtils::getInvoiceDueAmount($invoice);

        if ($candidateAmount > $dueAmount)
            throw new UserFriendlyException(Errors::INVOICE_OVERPAYMENT_DETECTED);

        $requester = $this->request->user();
        $authString = sprintf("id: %s (%s)", $requester->id, $requester->email);

        $raw = [
            'authorized_by' => $authString,
            'authorized_from' => $this->request->ip(),
            'note' => $this->request->has('note') ? $input['note'] : null
        ];

        $ret = $this->addTransaction($this, $invoice, $candidateAmount, 0.00, Utility::getRandomString(2),
                                     PaymentType::CREDIT, TransactionReasons::PAYMENT, json_encode($raw));

        $ret->raw_response = null;

        $wrappedResponse = new PaymentProcessorResponse();
        $wrappedResponse->type = PaymentProcessorResponseType::SUCCESS;
        $wrappedResponse->raw = $ret;

        return $wrappedResponse;
    }

    function callback(Request $request): JsonResponse
    {
        throw new NotSupportedException();
    }

    function refund(Transaction $transaction, Float $amount): PaymentProcessorResponse
    {
        throw new NotSupportedException();
    }

    function subscribe(Order $order): PaymentProcessorResponse
    {
        throw new NotSupportedException();
    }

    function unSubscribe(Order $order): PaymentProcessorResponse
    {
        throw new NotSupportedException();
    }

    function clearSavedData()
    {
        throw new NotSupportedException();
    }
}