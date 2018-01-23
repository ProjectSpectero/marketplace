<?php


namespace App\Http\Controllers\V1;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends V1Controller
{

    public function process (Request $request, String $processor, int $invoiceId) : JsonResponse
    {

    }

    /**
     * @param Request $request
     * @param String $processor
     * TODO: think about how to make this generic
     */
    public function callback (Request $request, String $processor)
    {

    }

    /**
     * @param Request $request
     * @param String $type (either 'transaction' or 'invoice'), worth noting that only credit type transactions may be refunded
     * @param int $reference (the transaction or invoice ID)
     * @return JsonResponse
     */
    public function refund (Request $request, String $type, int $reference) : JsonResponse
    {

    }

    public function subscribe (Request $request, int $orderId) : JsonResponse
    {

    }

    public function unsubscribe (Request $request, int $orderId) : JsonResponse
    {

    }
}