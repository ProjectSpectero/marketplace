<?php

namespace App\Libraries\Payment;


use App\Invoice;
use App\Order;
use App\Transaction;
use App\Constants\PaymentType;
use Illuminate\Http\Request;
use Srmklive\PayPal\Facades\PayPal;

class PaypalProcessor implements IPaymentProcessor
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

    function process(Invoice $invoice)
    {
        $this->invoice = $invoice;

        $this->processData($invoice);
        $this->data['type'] = 'payment';

        $response = $this->provider->setExpressCheckout($this->data);

        return $response['paypal_link'];
    }

    function callback(Request $request)
    {
        $token = $request->get('token');

        $response = $this->provider->getExpressCheckoutDetails($token);

        // throw exception here
        if ($response['BILLINGAGREEMENTACCEPTEDSTATUS'] == '0')
            return;

        $payerId = $response['PAYERID'];

        $response = $this->provider->doExpressCheckoutPayment($this->data, $token, $payerId);
        $response['redirect_url'] = url('/our/success/page');

        // TODO: throw exception
        if ($response['PAYMENTINFO_0_PAYMENTSTATUS'] != 'Completed')
            return;

        $this->data['transaction_id'] = $response['PAYMENTINFO_0_TRANSACTIONID'];

        if ($this->data['type'] == 'subscription')
            $this->createRecurringPaymentsProfile($token);

        $this->addTransaction($this->invoice->amount);

        return $response;
    }

    function refund(Transaction $transaction, Float $amount)
    {
        // TODO: throw NO_TRANSACTION_ID_FOUND or something
        if (! array_key_exists('transaction_id', $this->data))
            return;

        // TODO: throw exeption
        if ($amount > $transaction->amount)
            return;

        $refundResponse =  $this->provider->refundTransaction($this->data['transaction_id'], $amount);

        $this->addTransaction($amount);

        return $refundResponse;
    }

    function subscribe(Order $order)
    {
        $this->processData($order->invoice);
        $this->data['subscription_desc'] = "Monthly description default";
        $this->data['type'] = 'subscription';

        $response = $this->provider->setExpressCheckout($this->data);

        return $response['paypal_link'];
    }

    function unSubscribe(Order $order)
    {
        // TODO: Implement unSubscribe() method.
    }

    private function processData(Invoice $invoice)
    {
        $this->invoice = $invoice;

        $this->data['invoice_id'] = $invoice->id;

        $items = $this->processLineItems($invoice->order->lineItems);
        $this->data['items'] = $items;

        $this->data['invoice_description'] = "Default invoice description";
        $this->data['return_url'] = url('/payments/paypal/callback');
        $this->data['cancel_url'] = url('/cart');

        $total = 0;
        foreach($this->data['items'] as $item)
        {
            $total += $item['price'] * $item['qty'];
        }

        $this->data['total'] = $total;
    }

    private function processLineItems($lineItems)
    {
        $items = [];
        foreach ($lineItems as $lineItem)
        {
            $items[] = [
                'name' => $lineItem->description,
                'price' => $lineItem->amount,
                'qty' => $lineItem->qty
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

    private function addTransaction(Float $amount)
    {
        $transaction = new Transaction();

        $transaction->invoice_id = $this->invoice->id;
        $transaction->payment_processor = 'paypal';
        $transaction->reference = $this->data['transaction_id'];
        $transaction->type = $this->data['type'];
        $transaction->payment_type = PaymentType::DEBIT;
        $transaction->amount = $amount;
        $transaction->currency = $this->invoice->currency;
        $transaction->save();

        return $transaction;
    }
}