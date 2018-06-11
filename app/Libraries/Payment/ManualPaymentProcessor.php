<?php


namespace App\Libraries\Payment;


use App\Errors\NotSupportedException;
use App\Invoice;
use App\Models\Opaque\PaymentProcessorResponse;
use App\Order;
use App\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManualPaymentProcessor extends BasePaymentProcessor
{

    function getName(): string
    {
        return "MANUAL";
    }

    function getValidationRules(String $method): array
    {
        switch ($method)
        {
            case "process":
                return
                [

                ];

            default:
                return [];
        }
        throw new NotSupportedException();
    }

    function process(Invoice $invoice): PaymentProcessorResponse
    {
        // TODO: Implement process() method.
    }

    function callback(Request $request): JsonResponse
    {
        throw new NotSupportedException();
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

    function clearSavedData()
    {
        throw new NotSupportedException();
    }
}