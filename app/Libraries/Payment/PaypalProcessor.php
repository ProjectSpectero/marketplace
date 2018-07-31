<?php

namespace App\Libraries\Payment;


use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\PaymentProcessor;
use App\Constants\PaymentProcessorResponseType;
use App\Constants\RedirectType;
use App\Constants\ResponseType;
use App\Constants\TransactionReasons;
use App\Errors\NotSupportedException;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\Libraries\Utility;
use App\Models\Opaque\PaymentProcessorResponse;
use App\Order;
use App\Transaction;
use App\Constants\PaymentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Srmklive\PayPal\Facades\PayPal;

class PaypalProcessor extends BasePaymentProcessor
{
    private $provider;
    private $invoice;

    /**
     * PaypalProcessor constructor.
     */
    public function __construct(Request $request)
    {
        if (env('PAYPAL_ENABLED', false) != true)
            throw new UserFriendlyException(Messages::PAYMENT_PROCESSOR_NOT_ENABLED, ResponseType::BAD_REQUEST);

        $this->provider = PayPal::setProvider('express_checkout');

        parent::__construct($request);
    }

    function process(Invoice $invoice) : PaymentProcessorResponse
    {
        $this->invoice = $invoice;

        $data = $this->processInvoice($invoice, 'payment');

        $response = $this->provider->setExpressCheckout($data);

        $this->ensureSuccess($response, $data);

        $wrappedResponse = new PaymentProcessorResponse();
        $wrappedResponse->type = PaymentProcessorResponseType::REDIRECT;
        $wrappedResponse->subtype = RedirectType::EXTERNAL;
        $wrappedResponse->method = 'GET';
        $wrappedResponse->redirectUrl = $response['paypal_link'];

        return $wrappedResponse;
    }

    public function callback(Request $request) : JsonResponse
    {
        $ret = null;
        $token = $request->get('token');
        $mode = $request->get('mode');

        switch ($mode)
        {
            case "payment":
            case "recurring":

            $response = $this->provider->getExpressCheckoutDetails($token);
            $this->ensureSuccess($response);

            if ($response['BILLINGAGREEMENTACCEPTEDSTATUS'] == '0')
                throw new UserFriendlyException(Errors::BILLING_AGREEMENT_NOT_ACCEPTED, ResponseType::FORBIDDEN);

            $checkoutData = $this->provider->getExpressCheckoutDetails($token);

            // Extract the real invoice ID if it was partial, no changes if it wasn't.
            $invoiceId = $this->getMajorInvoiceIdFromPartialId($checkoutData['INVNUM']);

            // We cannot account for a payment without the relevant invoice
            $invoice = Invoice::findOrLogAndFail($invoiceId, $checkoutData);

            $data = $this->processInvoice($invoice, $mode);

            $payerId = $response['PAYERID'];

            /*
             * On first-call, this is what it looks like:
             * array:27 [
                  "TOKEN" => "EC-2RK44818WA308130X"
                  "BILLINGAGREEMENTID" => "B-6PK409170G5413528"
                  "SUCCESSPAGEREDIRECTREQUESTED" => "false"
                  "TIMESTAMP" => "2018-02-07T06:40:03Z"
                  "CORRELATIONID" => "6a0178c88d96b"
                  "ACK" => "Success"
                  "VERSION" => "123"
                  "BUILD" => "43477490"
                  "INSURANCEOPTIONSELECTED" => "false"
                  "SHIPPINGOPTIONISDEFAULT" => "false"
                  "PAYMENTINFO_0_TRANSACTIONID" => "11G11434ST9328303"
                  "PAYMENTINFO_0_TRANSACTIONTYPE" => "cart"
                  "PAYMENTINFO_0_PAYMENTTYPE" => "instant"
                  "PAYMENTINFO_0_ORDERTIME" => "2018-02-07T06:40:02Z"
                  "PAYMENTINFO_0_AMT" => "22.00"
                  "PAYMENTINFO_0_FEEAMT" => "0.94"
                  "PAYMENTINFO_0_TAXAMT" => "0.00"
                  "PAYMENTINFO_0_CURRENCYCODE" => "USD"
                  "PAYMENTINFO_0_PAYMENTSTATUS" => "Completed"
                  "PAYMENTINFO_0_PENDINGREASON" => "None"
                  "PAYMENTINFO_0_REASONCODE" => "None"
                  "PAYMENTINFO_0_PROTECTIONELIGIBILITY" => "Eligible"
                  "PAYMENTINFO_0_PROTECTIONELIGIBILITYTYPE" => "ItemNotReceivedEligible,UnauthorizedPaymentEligible"
                  "PAYMENTINFO_0_SELLERPAYPALACCOUNTID" => "billing-facilitator@spectero.com"
                  "PAYMENTINFO_0_SECUREMERCHANTACCOUNTID" => "FZX5DCUHMTH4Q"
                  "PAYMENTINFO_0_ERRORCODE" => "0"
                  "PAYMENTINFO_0_ACK" => "Success"
                ]

                * It's a bit different on subsequent requests (once payment has been claimed already).
             */
            $secondResponse = $this->provider->doExpressCheckoutPayment($data, $token, $payerId);
            $this->ensureSuccess($secondResponse);

            if ( (! isset($secondResponse['PAYMENTINFO_0_PAYMENTSTATUS']) && $secondResponse['PAYMENTINFO_0_PAYMENTSTATUS'] !== 'Completed')
                || (! isset($secondResponse['PAYMENTINFO_0_ACK']) && $secondResponse['PAYMENTINFO_0_ACK'] !== 'Success'))
                throw new UserFriendlyException(Errors::INCOMPLETE_PAYMENT, ResponseType::FORBIDDEN);

            $transactionId = $secondResponse['PAYMENTINFO_0_TRANSACTIONID'];
            $amount = $secondResponse['PAYMENTINFO_0_AMT'];
            $currency = $secondResponse['PAYMENTINFO_0_CURRENCYCODE'];
            $fee = $secondResponse['PAYMENTINFO_0_FEEAMT'];
            $tax = $secondResponse['PAYMENTINFO_0_TAXAMT'];

            $rawData = [
                'tokenLookup' => $response,
                'txnConfirmation' => $secondResponse
            ];

            $raw = json_encode($rawData);

            if ($currency !== $invoice->currency)
                throw new UserFriendlyException(Errors::INVOICE_CURRENCY_MISMATCH, ResponseType::FORBIDDEN);

            if ($mode == 'recurring')
            {
                $this->createRecurringPaymentsProfile($invoice, $token);
                $reason = TransactionReasons::SUBSCRIPTION;
            }
            else
                $reason = TransactionReasons::PAYMENT;

            $ret = $this->addTransaction($this, $invoice, $amount, $fee, $transactionId, PaymentType::CREDIT, $reason, $raw);
            break;

            case "ipn":
                // TODO: Figure out IPN support for recurring payments support over Paypal someday.
                // Using a custom string because this error will eventually be taken out.
                throw new UserFriendlyException("UNSUPPORTED_MODE");

                $request->merge(['cmd' => '_notify-validate']);
                $post = $request->all();

                $response = $this->provider->verifyIPN($post);
                break;
        }

        // There is no need to disclose the raw API response to the client, hide it.
        $ret->raw_response = null;

        return Utility::generateResponse($ret->toArray(), [], Messages::PAYMENT_PROCESSED);
    }

    public function refund(Transaction $transaction, Float $amount) : PaymentProcessorResponse
    {
        $refundResponse =  $this->provider->refundTransaction($transaction->reference, $amount);

        if ($amount < $transaction->amount)
            $reason = TransactionReasons::PARTIAL_REFUND;
        else
            $reason = TransactionReasons::REFUND;

        $wrappedResponse = new PaymentProcessorResponse();

        if ($refundResponse['ACK'] == 'Success')
        {
            $transactionId = $refundResponse['REFUNDTRANSACTIONID'];
            $raw = json_encode($refundResponse);

            $ret = $this->addTransaction($this, $transaction->invoice,
                                         $amount, 0.00,
                                         $transactionId, PaymentType::DEBIT,
                                         $reason, $raw,
                                         $transaction->id);
            $ret->raw_response = null;

            $wrappedResponse->type = PaymentProcessorResponseType::SUCCESS;
            $wrappedResponse->raw = $ret;
        }

        if (is_null($wrappedResponse->type))
            $wrappedResponse->type = PaymentProcessorResponseType::FAILURE;

        return $wrappedResponse;
    }

    public function subscribe(Order $order) : PaymentProcessorResponse
    {
        $data = $this->processInvoice($order->invoice, 'recurring');
        $data['subscription_desc'] = env('COMPANY_NAME', 'Spectero') . ' Order # ' . $order->id;

        $response = $this->provider->setExpressCheckout($data);

        $wrappedResponse = new PaymentProcessorResponse();
        $wrappedResponse->type = PaymentProcessorResponseType::REDIRECT;
        $wrappedResponse->redirectUrl = $response['paypal_link'];
        $wrappedResponse->raw = $response;

        return $wrappedResponse;
    }

    function unSubscribe(Order $order) : PaymentProcessorResponse
    {
        throw new NotSupportedException();
    }

    private function processInvoice (Invoice $invoice, String $mode = '') : array
    {
        $data = [];
        // Figure out how much is due on the invoice
        $amount = $this->getDueAmount($invoice);
        $data['total'] = $amount;

        if ($amount < $invoice->amount)
        {
            // At least one partial payment has been applied.
            // We need to mangle the items array.
            $data['items'][] = [
                'name' => 'Partial payment for ' . env('COMPANY_NAME') . ' Invoice #' . $invoice->id,
                'qty' => 1,
                'price' => $amount
            ];
            // Can no longer be JUST invoice ID, paypal tracks this and will reject the same ID having a payment applied.
            $data['invoice_id'] = $this->getPartialInvoiceId($invoice);
        }
        else
        {
            if (! is_null($invoice->order))
                $data['items'] = $this->processLineItems($invoice->order->lineItems);
            else
            {
                $data['items'][] = [
                    'name' => 'Invoice payment for ' . env('COMPANY_NAME') . ' Invoice #' . $invoice->id,
                    'qty' => 1,
                    'price' => $invoice->amount
                ];
            }
            $data['invoice_id'] = $invoice->id;
        }

        $data['invoice_description'] = $this->getInvoiceDescription($invoice);

        $data['return_url'] = url('/payment/paypal/callback?mode='.$mode);
        $data['cancel_url'] = url('/cart');

        return $data;
    }

    private function processLineItems($lineItems) : array
    {
        $items = [];
        foreach ($lineItems as $lineItem)
        {
            $items[] = [
                'name' => $lineItem->description,
                'price' => $lineItem->amount,
                'qty' => $lineItem->quantity
            ];
        }
        return $items;
    }

    private function ensureSuccess (Array $response, Array $data = [])
    {
        if (isset($response['ACK']))
        {
            if ($response['ACK'] == 'Success' || $response['ACK'] == 'SuccessWithWarning')
                return true;
        }

        Log::error("Unexpected response from Paypal API: " . json_encode($response) . "\n for data: " . json_encode($data));
        throw new UserFriendlyException(Errors::PAYMENT_FAILED, ResponseType::SERVICE_UNAVAILABLE);
    }
    private function createRecurringPaymentsProfile(Invoice $invoice, String $token)
    {
        $startdate = Carbon::now()->toAtomString();

        $data = $this->processInvoice($invoice);

        $data = [
            'PROFILESTARTDATE' => $startdate,
            'DESC' => $data['invoice_decs'],
            'BILLINGPERIOD' => 'Month', // Can be 'Day', 'Week', 'SemiMonth', 'Month', 'Year'
            'BILLINGFREQUENCY' => 1, //
            'AMT' => $invoice->amount, // Billing amount for each billing cycle
            'CURRENCYCODE' => $invoice->currency, // Currency code
        ];
        $response = $this->provider->createRecurringPaymentsProfile($data, $token);

        return $response;
    }

    public function getName() : string
    {
        return PaymentProcessor::PAYPAL;
    }

    public function getValidationRules (String $method) : array
    {
        switch ($method)
        {
            case 'callback':
                return [
                    'token' => 'required',
                    'mode' => 'required'
                ];

            default:
                return [];
        }
    }

    public function clearSavedData()
    {
       throw new NotSupportedException();
    }


}