<?php

namespace App\Libraries\Payment;


use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\PaymentProcessor;
use App\Constants\PaymentProcessorResponseType;
use App\Constants\ResponseType;
use App\Constants\TransactionReasons;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\Libraries\Utility;
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

        $data = $this->processInvoice($invoice, 'payment');

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
        $mode = $request->get('mode');
        $response = $this->provider->getExpressCheckoutDetails($token);

        if ($response['BILLINGAGREEMENTACCEPTEDSTATUS'] == '0')
            throw new UserFriendlyException(Errors::BILLING_AGREEMENT_NOT_ACCEPTED, ResponseType::FORBIDDEN);

        $checkoutData = $this->provider->getExpressCheckoutDetails($token);
        $invoice = Invoice::find($checkoutData['INVNUM']);

        $data = $this->processInvoice($invoice, $mode);

        $payerId = $response['PAYERID'];
        $response = $this->provider->doExpressCheckoutPayment($data, $token, $payerId);
        $response['redirect_url'] = url('/our/success/page');

        if ($response['PAYMENTINFO_0_PAYMENTSTATUS'] != 'Completed')
            throw new UserFriendlyException(Errors::INCOMPLETE_PAYMENT, ResponseType::FORBIDDEN);

        $transactionId = $response['PAYMENTINFO_0_TRANSACTIONID'];

        $mode = $request->get('mode');
        if ($mode == 'recurring')
        {
            $this->createRecurringPaymentsProfile($invoice, $token);
            $reason = TransactionReasons::SUBSCRIPTION;
        }
        else
            $reason = TransactionReasons::PAYMENT;

        $amount = $response['PAYMENTINFO_0_AMT'];

        $this->addTransaction($this, $invoice, $amount, $transactionId, PaymentType::DEBIT, $reason);

        return Utility::generateResponse($response, [], Messages::PAYMENT_PROCESSED);
    }

    function refund(Transaction $transaction, Float $amount) : PaymentProcessorResponse
    {
        if ($amount > $transaction->amount)
            throw new UserFriendlyException(Errors::REFUND_AMMOUNT_IS_BIGGER_THAN_TRANSACTION);

        $refundResponse =  $this->provider->refundTransaction($transaction->id, $amount);

        if ($amount < $transaction->amount)
            $reason = TransactionReasons::PARTIAL_REFUND;
        else
            $reason = TransactionReasons::REFUND;

        if ($refundResponse['ACK'] == 'Success')
            $this->addTransaction($this, $transaction->invoice, $amount, $transaction->reference, PaymentType::DEBIT, $reason);

        return $refundResponse;
    }

    function subscribe(Order $order) : PaymentProcessorResponse
    {
        $data = $this->processInvoice($order->invoice, 'recurring');
        $data['subscription_desc'] = "Monthly description default";

        $response = $this->provider->setExpressCheckout($data);

        $wrappedResponse = new PaymentProcessorResponse();
        $wrappedResponse->type = PaymentProcessorResponseType::REDIRECT;
        $wrappedResponse->redirectUrl = $response['paypal_link'];
        $wrappedResponse->raw = $response;

        return $wrappedResponse;
    }

    function unSubscribe(Order $order) : PaymentProcessorResponse
    {
        // TODO: Implement unSubscribe() method.
    }

    private function processInvoice (Invoice $invoice, String $mode) : array
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

        $data['return_url'] = url('/v1/payment/paypal/callback?mode='.$mode);
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
}