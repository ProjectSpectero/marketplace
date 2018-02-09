<?php


namespace App\Libraries\Payment;


use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\PaymentProcessor;
use App\Constants\PaymentProcessorResponseType;
use App\Constants\PaymentType;
use App\Constants\ResponseType;
use App\Constants\TransactionReasons;
use App\Constants\UserMetaKeys;
use App\Errors\NotSupportedException;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\Models\Opaque\PaymentProcessorResponse;
use App\Order;
use App\Transaction;
use App\User;
use App\UserMeta;
use Cartalyst\Stripe\Exception\MissingParameterException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Cartalyst\Stripe\Stripe;

class StripeProcessor extends BasePaymentProcessor
{

    private $provider;
    private $request;

    /**
     * StripeProcessor constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        if (env('STRIPE_ENABLED', false) != true)
            throw new UserFriendlyException(Messages::PAYMENT_PROCESSOR_NOT_ENABLED, ResponseType::BAD_REQUEST);

        $this->provider = new Stripe(env('STRIPE_MODE') == 'sandbox' ? env('STRIPE_SANDBOX_SECRET_KEY') : env('STRIPE_LIVE_SECRET_KEY'));
        $this->request = $request;
    }

    function getName(): string
    {
        return PaymentProcessor::STRIPE;
    }

    function process(Invoice $invoice): PaymentProcessorResponse
    {
        // First check so we can bail if invoice doesn't need paying
        $dueAmount = $this->getDueAmount($invoice);

        $user = $this->request->user();
        $token = $this->request->get('stripeToken');

        $customerId = $this->resolveCustomer($user, $token);

        $metadata = [
            'invoiceId' => $invoice->id,
            'orderId' => $invoice->order->id != null ? $invoice->order->id : null
        ];

        try
        {
            $charge = $this->provider
                ->charges()
                ->create([
                             'customer' => $customerId,
                             'currency' => $invoice->currency,
                             'amount'   => $dueAmount,
                             'statement_descriptor' => env('COMPANY_NAME', 'Spectero') . ' Invoice ' . $invoice->id,
                             'metadata' => $metadata,
                             'expand' => [ "balance_transaction" ]
                         ]);
        }
        catch (MissingParameterException $silenced)
        {
            throw new UserFriendlyException(Errors::INVALID_STRIPE_TOKEN);
        }

        $this->ensureSuccess($charge);

        $transactionId = $charge['id'];
        $amount = $charge['amount'] / 100;
        $currency = $charge['currency'];
        $fee = $charge['balance_transaction']['fee'] / 100;
        $reason = TransactionReasons::PAYMENT;
        $raw = json_encode($charge);

        // Stripe returns the currency in lowercase
        if (strtoupper($currency) !== $invoice->currency)
            throw new UserFriendlyException(Errors::INVOICE_CURRENCY_MISMATCH, ResponseType::FORBIDDEN);

        $ret = $this->addTransaction($this, $invoice, $amount, $fee, $transactionId, PaymentType::CREDIT, $reason, $raw);

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
        $refundResponse =  $this->provider->refunds()
            ->create($transaction->reference, $amount);

        if ($amount < $transaction->amount)
            $reason = TransactionReasons::PARTIAL_REFUND;
        else
            $reason = TransactionReasons::REFUND;

        $this->ensureSuccess($refundResponse, 'refund');

        $transactionId = $refundResponse['id'];
        $raw = json_encode($refundResponse);

        $ret = $this->addTransaction($this, $transaction->invoice,
                                     $amount, 0.00,
                                     $transactionId, PaymentType::DEBIT,
                                     $reason, $raw,
                                     $transaction->id);
        $ret->raw_response = null;

        $wrappedResponse = new PaymentProcessorResponse();
        $wrappedResponse->type = PaymentProcessorResponseType::SUCCESS;
        $wrappedResponse->raw = $ret;

        return $wrappedResponse;
    }

    function subscribe(Order $order): PaymentProcessorResponse
    {
        // TODO: Implement subscribe() method.
    }

    function unSubscribe(Order $order): PaymentProcessorResponse
    {
        // TODO: Implement unSubscribe() method.
    }

    private function resolveCustomer (User $user, String $token)
    {
        $metaloaded = false;

        try
        {
            $customerId = UserMeta::loadMeta($user, UserMetaKeys::StripeCustomerIdentifier, true)->meta_value;
            $metaloaded = true;
        }
        catch (ModelNotFoundException $silenced)
        {
            $customer = $this->provider
                ->customers()
                ->create([
                             'email' => $user->email,
                             'source' => $token
                         ]);

            UserMeta::addOrUpdateMeta($user, UserMetaKeys::StripeCustomerIdentifier, $customer['id']);
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::StripeCardToken, $token);
            $customerId = $customer['id'];
        }

        if ($metaloaded)
        {
            $storedToken = UserMeta::loadMeta($user, UserMetaKeys::StripeCardToken);
            if ($storedToken != null && $storedToken !== $token)
            {
                // Token changed, let us update the customer

                try
                {
                    $this->provider
                        ->customers()
                        ->update($customerId, [
                            'source' => $token
                        ]);
                }
                catch (MissingParameterException $exception)
                {
                    throw new UserFriendlyException(Errors::INVALID_STRIPE_TOKEN);
                }
                UserMeta::addOrUpdateMeta($user, UserMetaKeys::StripeCardToken, $token);
            }

        }

        return $customerId;
    }

    public function getValidationRules (String $method) : array
    {
        switch ($method)
        {
            case 'process':
                return [
                    'stripeToken' => 'required',
                ];

            default:
                return [];
        }
    }

    private function ensureSuccess (Array $data, String $caller = 'process')
    {
        $failed = false;
        $statusCheck = isset($data['status']) && $data['status'] == 'succeeded';
        switch ($caller)
        {
            case 'process':
                if (! $statusCheck
                && (! isset($data['captured']) && $data['captured'] !== true)
                && (! isset($data['paid']) && $data['paid'] !== true))
                    $failed = true;
                break;

            case 'refund':
                if (! $statusCheck)
                    $failed = true;
                break;
        }

        if ($failed)
            throw new UserFriendlyException(Errors::PAYMENT_FAILED, ResponseType::FORBIDDEN);
    }
}