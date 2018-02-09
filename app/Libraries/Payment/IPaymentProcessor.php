<?php


namespace App\Libraries\Payment;


use App\Invoice;
use App\Models\Opaque\PaymentProcessorResponse;
use App\Order;
use App\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface IPaymentProcessor
{
    function getName () : string;
    function process (Invoice $invoice) : PaymentProcessorResponse;
    function callback (Request $request) : JsonResponse;
    function refund (Transaction $transaction, Float $amount) : PaymentProcessorResponse;
    function subscribe (Order $order) : PaymentProcessorResponse;
    function unSubscribe (Order $order) : PaymentProcessorResponse;
    function getValidationRules(String $method): array;
}