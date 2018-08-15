<?php


namespace App\Libraries\Payment;


use App\Constants\Currency;
use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\PaymentProcessor;
use App\Constants\PaymentProcessorResponseType;
use App\Constants\PaymentType;
use App\Constants\RedirectType;
use App\Constants\ResponseType;
use App\Constants\TransactionReasons;
use App\Errors\NotSupportedException;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\Libraries\APIUtils;
use App\Libraries\BillingUtils;
use App\Libraries\Utility;
use App\Mail\CryptoPaymentFailed;
use App\Models\Opaque\PaymentProcessorResponse;
use App\Order;
use App\Transaction;
use App\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class CryptoProcessor extends BasePaymentProcessor
{
    const targetVersion = '2018-03-22';
    const endpoint = 'https://api.commerce.coinbase.com/';

    const apiKeyHeaderName = 'X-CC-Api-Key';
    const apiVersionHeaderName = 'X-CC-Version';
    const webhookSignatureHeaderName = 'X-CC-Webhook-Signature';

    const queryTimeoutSettings = 5;
    const chargeExpiryMinutes = 15;

    private $client;
    private $headers;

    public function __construct(Request $request)
    {
        if (env('CRYPTO_ENABLED', false) != true)
            throw new UserFriendlyException(Messages::PAYMENT_PROCESSOR_NOT_ENABLED, ResponseType::BAD_REQUEST);

        parent::__construct($request);

        $this->client = new Client([
                                       'base_uri' => self::endpoint,
                                       'timeout' => self::queryTimeoutSettings
                                   ]);

        $this->headers = [
            self::apiKeyHeaderName => env('CRYPTO_MODE', 'sandbox') == 'sandbox' ? env('CRYPTO_SANDBOX_API_KEY') : env('CRYPTO_LIVE_API_KEY'),
            self::apiVersionHeaderName => self::targetVersion,
            'Content-Type' => 'application/json'
        ];
    }

    function getName(): string
    {
        return PaymentProcessor::CRYPTO;
    }

    function process(Invoice $invoice): PaymentProcessorResponse
    {
        $dueAmount = BillingUtils::getInvoiceDueAmount($invoice);
        $user = $this->request->user();

        $key = $this->formatCacheKey($invoice, $user, $dueAmount);

        if (\Cache::has($key))
            return \Cache::get($key);

        $payload = [
            'name' => env('COMPANY_NAME', 'Spectero') . ', Inc.',
            'description' => 'Crypto Payment for ' . env('COMPANY_NAME', 'Spectero') . " Invoice #$invoice->id",
            'local_price' => [
                'amount' => $dueAmount,
                'currency' => $invoice->currency
            ],
            'pricing_type' => 'fixed_price',
            'metadata' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email
                ],
                'invoice' => [
                    'id' => $invoice->id,
                    'dueAmount' => $dueAmount,
                    'currency' => $invoice->currency
                ],
            ]
        ];

        $response = APIUtils::request($this->client, 'POST', 'charges', $payload, $this->headers, true);

        $this->ensureSuccess(self::METHOD_PROCESS, $response, $payload);

        $response = $response['data'];

        $wrappedResponse = new PaymentProcessorResponse();
        $wrappedResponse->type = PaymentProcessorResponseType::REDIRECT;
        $wrappedResponse->subtype = RedirectType::EXTERNAL;
        $wrappedResponse->method = 'GET';
        $wrappedResponse->redirectUrl = $response['hosted_url'];

        $cacheUntil = Carbon::parse($response['expires_at']);

        \Cache::put($key, $wrappedResponse, $cacheUntil);

        return $wrappedResponse;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function callback(Request $request): JsonResponse
    {
        // This is what validates the request's HMAC signature.
        $this->validateSignature($request);

        $data = $request->all();
        $event = $data['event'];

        /** @var Invoice $invoice */
        $invoice = Invoice::findOrLogAndFail($event['data']['metadata']['invoice']['id'], $data);

        /** @var User $user */
        $user = User::findOrLogAndFail($event['data']['metadata']['user']['id'], $data);

        $ret = null;

        switch ($event['type'])
        {
            case "charge:failed":
                Mail::to($user->email)->queue(new CryptoPaymentFailed($invoice, $user));

                // Empty return, so the webhook consumer is aware that it succeeded (despite a "fail" event being handled)
                break;

            case "charge:confirmed":
                // OK bob, we got paid. Let's validate that everything is as it seems
                $this->ensureSuccess(self::METHOD_CALLBACK, $data);
                $paidReference = $event['data']['metadata']['invoice'];

                $paidAmount = $paidReference['dueAmount'];
                $paidCurrency = $paidReference['currency'];

                if (strcasecmp($paidCurrency, $invoice->currency) !== 0)
                    throw new UserFriendlyException(Errors::INVOICE_CURRENCY_MISMATCH);

                // TODO: Incorporate fee calculation here
                $ret = $this->addTransaction($this, $invoice, $paidAmount, 0, $event['data']['code'], PaymentType::CREDIT, TransactionReasons::PAYMENT, json_encode($data));

                // There is no need to disclose the raw API response to the client, hide it.
                $ret->raw_response = null;
                $ret = $ret->toArray();

                break;
        }

        return Utility::generateResponse($ret, [], Messages::PAYMENT_PROCESSED);
    }

    private function validateSignature (Request $request)
    {
        $toBeMatchedAgainst = $request->header(self::webhookSignatureHeaderName);

        $contents = $request->getContent();
        $webhookSecret = env('CRYPTO_MODE', 'sandbox') == 'sandbox' ? env('CRYPTO_SANDBOX_WEBHOOK_SECRET') : env('CRYPTO_LIVE_WEBHOOK_SECRET');

        if (empty($toBeMatchedAgainst))
            throw new UserFriendlyException(Errors::HMAC_HEADER_MISSING);

        if (empty($webhookSecret))
        {
            Log::error("Bad configuration for the crypto provider: enabled, but the webhook secret is empty!");
            throw new UserFriendlyException(Errors::REQUEST_FAILED, ResponseType::SERVICE_UNAVAILABLE);
        }

        $ourHash = hash_hmac('sha256', $contents, $webhookSecret);

        if ($toBeMatchedAgainst !== $ourHash)
            throw new UserFriendlyException(Errors::HMAC_MISMATCH, ResponseType::FORBIDDEN);
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

    function getValidationRules(String $method): array
    {
        $data = [];

        switch ($method)
        {
            // Don't need any user submitted data to generate the link, hence do nothing.
            case self::METHOD_PROCESS:
                break;

            case self::METHOD_CALLBACK:
                $data = [
                    'id' => 'required',
                    'event' => 'required|array',
                    'event.id' => 'required|alpha_dash',
                    'event.type' => [ 'required', Rule::in(['charge:confirmed', 'charge:failed'])],
                    'event.api_version' => 'required|equals:' . self::targetVersion,
                    'event.data' => 'required|array',
                    'event.data.code' => 'required',
                    'event.data.metadata' => 'required|array',
                    'event.data.metadata.user' => 'required|array',
                    'event.data.metadata.user.id' => 'required|integer',
                    'event.data.metadata.invoice' => 'required|array',
                    'event.data.metadata.invoice.id' => 'required|integer',
                    'event.data.metadata.invoice.dueAmount' => 'required|numeric',
                    'event.data.metadata.invoice.currency' => [ 'required', Rule::in(Currency::getConstants()) ]
                ];

                break;

        }

        return $data;
    }

    function clearSavedData()
    {
        throw new NotSupportedException();
    }

    private function formatCacheKey (Invoice $invoice, User $user, float $amount)
    {
        return "crypto.payment.$invoice->id.$user->id.$amount";
    }

    private function ensureSuccess (string $method, array $response, array $sentData = [])
    {
        $error = false;

        if (isset($response['error']))
            $error = true;

        if (! $error)
        {
            switch ($method)
            {
                // Methodwise, further validation, if needed.
                case self::METHOD_PROCESS:
                    if (! isset($response['data']) || ! isset($response['data']['hosted_url']))
                        $error = true;
                    break;

                case self::METHOD_CALLBACK:
                    if (! isset($response['event']['data']['payments']) || count($response['event']['data']['payments']) == 0 ||
                        ! isset($response['event']['data']['confirmed_at']))
                        $error = true;
                    break;
            }
        }

        if ($error)
        {
            Log::error("Unexpected response from Crypto API: " . json_encode($response) . "\n for data: " . json_encode($sentData));
            throw new UserFriendlyException(Errors::PAYMENT_FAILED, ResponseType::SERVICE_UNAVAILABLE);
        }
    }
}