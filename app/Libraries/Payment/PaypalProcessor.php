<?php

namespace App\Libraries\Payment;


use App\Constants\PaymentProcessor;
use App\Constants\PaymentProcessorResponseType;
use App\Constants\TransactionReasons;
use App\Invoice;
use App\Models\Opaque\PaymentProcessorResponse;
use App\Order;
use App\Transaction;
use App\Constants\PaymentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Srmklive\PayPal\Facades\PayPal;

class PaypalProcessor extends BasePaymentProcessor
{
    private $provider;
    private $invoice;
    private $data = [];

    /**
     * PaypalProcessor constructor.
     */
    public function __construct()
    {
        $this->provider = PayPal::setProvider('express_checkout');
    }

    function process(Invoice $invoice) : PaymentProcessorResponse
    {
        $this->invoice = $invoice;

        $data = $this->processInvoice($invoice);
        $data['type'] = 'payment';

        $response = $this->provider->setExpressCheckout($data);

        $wrappedResponse = new PaymentProcessorResponse();
        $wrappedResponse->type = PaymentProcessorResponseType::REDIRECT;
        $wrappedResponse->redirectUrl = $response['paypal_link'];
        $wrappedResponse->raw = $response;

        return $wrappedResponse;
    }

    function callback(Request $request) : JsonResponse
    {
        $token = $request->get('token');

        $response = $this->provider->getExpressCheckoutDetails($token);

        // throw exception here
        if ($response['BILLINGAGREEMENTACCEPTEDSTATUS'] == '0')
            return null;

        $payerId = $response['PAYERID'];

        $response = $this->provider->doExpressCheckoutPayment($this->data, $token, $payerId);
        $response['redirect_url'] = url('/our/success/page');

        // TODO: throw exception
        if ($response['PAYMENTINFO_0_PAYMENTSTATUS'] != 'Completed')
            return null;

        $transactionId = $response['PAYMENTINFO_0_TRANSACTIONID'];

        if ($this->data['type'] == 'subscription')
            $this->createRecurringPaymentsProfile($token);

        // TODO: Figure out what you were actually paid, do NOT use invoice->amount for amount. Make this compile
        //$this->addTransaction($this, $transaction->invoice(), $amount, $transactionId, PaymentType::DEBIT, $reason);

        return $response;
    }

    function refund(Transaction $transaction, Float $amount) : PaymentProcessorResponse
    {
        // TODO: throw NO_TRANSACTION_ID_FOUND or something
        if (! array_key_exists('transaction_id', $this->data))
            return null;

        // TODO: throw exeption
        if ($amount > $transaction->amount)
            return null;

        $refundResponse =  $this->provider->refundTransaction($this->data['transaction_id'], $amount);

        if ($amount < $transaction->amount)
            $reason = TransactionReasons::PARTIAL_REFUND;
        else
            $reason = TransactionReasons::REFUND;

        // TODO: Only add this if the refund is VERIFIED to have been successful
        $this->addTransaction($this, $transaction->invoice(), $amount, $transaction->reference, PaymentType::DEBIT, $reason);

        return $refundResponse;
    }

    function subscribe(Order $order) : PaymentProcessorResponse
    {
        $this->processData($order->invoice);
        $this->data['subscription_desc'] = "Monthly description default";
        $this->data['type'] = 'subscription';

        $response = $this->provider->setExpressCheckout($this->data);

        return $response['paypal_link'];
    }

    function unSubscribe(Order $order) : PaymentProcessorResponse
    {
        // TODO: Implement unSubscribe() method.
    }

    private function processInvoice (Invoice $invoice) : array
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
            $data['items'] = $this->processLineItems($invoice->order->lineItems);
            $data['invoice_id'] = $invoice->id;
        }

        $data['invoice_description'] = $this->getInvoiceDescription($invoice);

        $data['return_url'] = url('/payments/paypal/callback');
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

    private function createRecurringPaymentsProfile($token)
    {
        $startdate = Carbon::now()->toAtomString();

        $data = [
            'PROFILESTARTDATE' => $startdate,
            'DESC' => $this->data['invoice_decs'],
            'BILLINGPERIOD' => 'Month', // Can be 'Day', 'Week', 'SemiMonth', 'Month', 'Year'
            'BILLINGFREQUENCY' => 1, //
            'AMT' => 10, // Billing amount for each billing cycle
            'CURRENCYCODE' => 'USD', // Currency code
        ];
        $response = $this->provider->createRecurringPaymentsProfile($data, $token);

        return $response;
    }

    public function getName() : string
    {
        return PaymentProcessor::PAYPAL;
    }
}