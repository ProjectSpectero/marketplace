<?php


namespace App\Libraries\Payment;

use App\Constants\Errors;
use App\Constants\PaymentProcessor;
use App\Constants\PaymentProcessorResponseType;
use App\Constants\PaymentType;
use App\Constants\ResponseType;
use App\Constants\TransactionReasons;
use App\Errors\NotSupportedException;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\Libraries\BillingUtils;
use App\Libraries\Utility;
use App\Models\Opaque\PaymentProcessorResponse;
use App\Order;
use App\Transaction;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountCreditProcessor extends BasePaymentProcessor
{

    private $request;

    public function __construct(Request $request)
    {
        if (env('ACCOUNT_CREDIT_ENABLED', false) != true)
            throw new UserFriendlyException(Messages::PAYMENT_PROCESSOR_NOT_ENABLED, ResponseType::BAD_REQUEST);

        $this->request = $request;
    }

    function getName(): string
    {
        return PaymentProcessor::ACCOUNT_CREDIT;
    }

    /**
     * @param Invoice $invoice
     * @return PaymentProcessorResponse
     * @throws UserFriendlyException
     * @throws \Exception
     * @throws \Throwable
     */
    function process(Invoice $invoice): PaymentProcessorResponse
    {
        /** @var User $user */
        $user = $this->request->user();

        $credit = $user->credit;
        $dueAmount = BillingUtils::getInvoiceDueAmount($invoice);

        if ($credit > 0)
        {
            // There's at least some to apply, let's go take a look.
            if ($invoice->currency != $invoice->user->credit_currency)
                throw new UserFriendlyException(Errors::INVOICE_CURRENCY_MISMATCH);

            $amountToCharge = 0;
            $was = $credit;

            if ($credit >= $dueAmount)
            {
                // Means we can do a full charge, that's good.
                $credit = $credit - $dueAmount;
                $amountToCharge = $dueAmount;
            }
            else
            {
                $amountToCharge = $credit;
                $credit = 0;
            }
            $now = $credit;

            $raw = json_encode([
                'was' => $was,
                'now' => $now
            ]);


            \DB::transaction(function () use ($user, $credit)
            {
                $user->credit = $credit;
                $user->saveOrFail();
            });

            $ret = $this->addTransaction($this, $invoice, $amountToCharge, 0.00, Utility::getRandomString(2), PaymentType::CREDIT, TransactionReasons::PAYMENT, $raw);
            $ret->raw_response = null;

            $wrappedResponse = new PaymentProcessorResponse();
            $wrappedResponse->type = PaymentProcessorResponseType::SUCCESS;
            $wrappedResponse->raw = $ret;

            return $wrappedResponse;
        }
        throw new UserFriendlyException(Errors::ZERO_CREDIT_BALANCE, ResponseType::FORBIDDEN);
    }

    function refund(Transaction $transaction, Float $amount): PaymentProcessorResponse
    {
        if ($amount < $transaction->amount)
            $reason = TransactionReasons::PARTIAL_REFUND;
        else
            $reason = TransactionReasons::REFUND;


        $user = $transaction->invoice->user;
        $newCredit = $user->credit + $amount;
        $raw = json_encode([
            'was' => $user->credit,
            'now' => $user->credit + $amount
        ]);

        // First add entry, then refund back ;V
        $ret = $this->addTransaction($this, $transaction->invoice, $amount, 0.00, Utility::getRandomString(2), PaymentType::DEBIT, $reason, $raw);
        $user->credit = $newCredit;
        $user->saveOrFail();

        $ret->raw_response = null;

        $wrappedResponse = new PaymentProcessorResponse();
        $wrappedResponse->type = PaymentProcessorResponseType::SUCCESS;
        $wrappedResponse->raw = $ret;

        return $wrappedResponse;
    }

    function getValidationRules(String $method): array
    {
        return [];
    }

    function callback(Request $request): JsonResponse
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