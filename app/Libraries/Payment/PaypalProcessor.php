<?php

namespace App\Libraries\Payment;


use App\Invoice;
use App\Order;
use App\Transaction;
use Illuminate\Http\Request;
use Srmklive\PayPal\Facades\PayPal;

class PaypalProcessor implements IPaymentProcessor
{
    private $provider;

    /**
     * PaypalProcessor constructor.
     */
    public function __construct()
    {
        $this->provider = PayPal::setProvider('express_checkout');
    }

    function process(Invoice $invoice)
    {
        $data = [];

        $data['invoice_id'] = $invoice->id;

        $items = $this->processLineItems($invoice->order->lineItems);
        $data['items'] = $items;

        $data['invoice_description'] = "Test order";
        $data['return_url'] = url('/some/sucess/page');
        $data['cancel_url'] = url('/cart');

        $total = 0;
        foreach($data['items'] as $item)
        {
            $total += $item['price'] * $item['qty'];
        }

        $data['total'] = $total;

        $response = $this->provider->setExpressCheckout($data);

        return $response['paypal_link'];
    }

    function callback(Request $request)
    {
        // TODO: Implement callback() method.
    }

    function refund(Transaction $transaction)
    {
        // TODO: Implement refund() method.
    }

    function subscribe(Order $order)
    {
        // TODO: Implement subscribe() method.
    }

    function unSubscribe(Order $order)
    {
        // TODO: Implement unSubscribe() method.
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
}