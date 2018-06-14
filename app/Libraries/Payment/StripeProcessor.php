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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Cartalyst\Stripe\Stripe;

class StripeProcessor extends BasePaymentProcessor
{

    private $provider;

    /**
     * StripeProcessor constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        if (env('STRIPE_ENABLED', false) != true)
            throw new UserFriendlyException(Messages::PAYMENT_PROCESSOR_NOT_ENABLED, ResponseType::BAD_REQUEST);

        $this->provider = new Stripe(env('STRIPE_MODE', 'sandbox') == 'sandbox' ? env('STRIPE_SANDBOX_SECRET_KEY') : env('STRIPE_LIVE_SECRET_KEY'));

        parent::__construct($request);
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
        $save = $this->request->has('save') ? (bool) $this->request->get('save') : false;

        $token = null;

        if ($this->request->has('stripeToken'))
        {
            $token = $this->request->get('stripeToken');
            $customerId = $this->resolveCustomer($user, $token, $save);
        }
        else
        {
            // Let's see if customer has a saved token instead.
            $customerId = UserMeta::loadMeta($user, UserMetaKeys::StripeCustomerIdentifier);
        }

        if ($customerId == null && $token == null)
            throw new UserFriendlyException(Errors::INVALID_STRIPE_TOKEN);

        // This builder check is completely idiotic, but necessary because Eloquent scopes cannnot return null.
        if ($customerId instanceof Builder)
            throw new UserFriendlyException(Errors::NO_STORED_CARD);



        if ($customerId instanceof UserMeta)
            $customerId = $customerId->meta_value;

        $order = $invoice->order;
        if ($order != null)
            $orderId = $order->id;
        else
            $orderId = null;

        $metadata = [
            'invoiceId' => $invoice->id,
            'orderId' => $orderId
        ];

        $companyName = env('COMPANY_NAME', 'Spectero');
        $statementDescriptor = $companyName. ' #' . $invoice->id;
        if (strlen($statementDescriptor) > 22)
            $statementDescriptor = 'SPCInv #' . $invoice->id;

        try
        {
            $descriptor = [
                'currency' => $invoice->currency,
                'amount'   => $dueAmount,
                'statement_descriptor' => $statementDescriptor,
                'metadata' => $metadata,
                'expand' => [ "balance_transaction" ]
            ];

            if (! is_null($customerId))
                $descriptor['customer'] = $customerId;
            else
                $descriptor['source'] = $token;

            $charge = $this->provider
                ->charges()
                ->create($descriptor);
        }
        catch (MissingParameterException $silenced)
        {
            throw new UserFriendlyException(Errors::INVALID_STRIPE_TOKEN);
        }

        $this->ensureSuccess($charge);

        if ($save)
        {
            $cardIdentifier = $charge['source']['brand'] . ' ' . $charge['source']['last4'] . ' ' . $charge['source']['exp_month'] . '/' . $charge['source']['exp_year'];
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::StoredCardIdentifier, $cardIdentifier);
        }

        // TODO: integrate fraud check here before accepting transaction.

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
        throw new NotSupportedException();
    }

    function unSubscribe(Order $order): PaymentProcessorResponse
    {
        throw new NotSupportedException();
    }

    private function resolveCustomer (User $user, String $token, bool $save = false)
    {
        $metaloaded = false;

        try
        {
            $customerId = UserMeta::loadMeta($user, UserMetaKeys::StripeCustomerIdentifier, true)->meta_value;
            $metaloaded = true;
        }
        catch (ModelNotFoundException $silenced)
        {
            try
            {
                $customer = $this->provider
                    ->customers()
                    ->create([
                                 'email' => $user->email,
                                 'source' => $token
                             ]);
            }
            catch (MissingParameterException $silenced)
            {
                throw new UserFriendlyException(Errors::INVALID_STRIPE_TOKEN);
            }

            if ($save)
            {
                UserMeta::addOrUpdateMeta($user, UserMetaKeys::StripeCustomerIdentifier, $customer['id']);
                UserMeta::addOrUpdateMeta($user, UserMetaKeys::StripeCardToken, $token);
            }

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
                    'stripeToken' => 'sometimes|alpha_dash',
                    'save' => 'sometimes|boolean'
                ];

            default:
                return [];
        }
    }

    public function clearSavedData()
    {
        $user = $this->request->user();

        try
        {
            /** @var UserMeta $token */
            $token = UserMeta::loadMeta($user, UserMetaKeys::StripeCardToken, true);

            /** @var UserMeta $cust */
            $cust = UserMeta::loadMeta($user, UserMetaKeys::StripeCustomerIdentifier, true);


            /** @var UserMeta $card */
            $card = UserMeta::loadMeta($user, UserMetaKeys::StoredCardIdentifier, true);
        }
        catch (ModelNotFoundException $exception)
        {
            throw new UserFriendlyException(Errors::NO_STORED_CARD);
        }

        $token->delete();
        $cust->delete();
        $card->delete();
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