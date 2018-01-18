<?php


namespace App\Libraries\Payment;


use App\Invoice;
use App\Order;
use App\Transaction;
use Illuminate\Http\Request;

interface IPaymentProcessor
{
    function getName ();
    function process (Invoice $invoice);
    function callback (Request $request);
    function refund (Transaction $transaction, Float $amount);
    function subscribe (Order $order);
    function unSubscribe (Order $order);
}