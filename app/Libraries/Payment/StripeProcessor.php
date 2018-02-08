<?php


namespace App\Libraries\Payment;


use App\Constants\PaymentProcessor;
use App\Invoice;
use App\Models\Opaque\PaymentProcessorResponse;
use App\Order;
use App\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeProcessor implements IPaymentProcessor
{

    function getName(): string
    {
        return PaymentProcessor::STRIPE;
    }

    function process(Invoice $invoice): PaymentProcessorResponse
    {
        // TODO: Implement process() method.
    }

    function callback(Request $request): JsonResponse
    {
        // TODO: Implement callback() method.
    }

    function refund(Transaction $transaction, Float $amount): PaymentProcessorResponse
    {
        // TODO: Implement refund() method.
    }

    function subscribe(Order $order): PaymentProcessorResponse
    {
        // TODO: Implement subscribe() method.
    }

    function unSubscribe(Order $order): PaymentProcessorResponse
    {
        // TODO: Implement unSubscribe() method.
    }
}