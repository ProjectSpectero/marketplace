<?php


namespace App\Http\Controllers\V1;


use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\ResponseType;
use App\Invoice;
use App\Libraries\Payment\PaypalProcessor;
use App\Order;
use App\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends V1Controller
{

    public function process (Request $request, String $processor, int $invoiceId) : JsonResponse
    {
        try
        {
            $invoice = Invoice::findOrFail($invoiceId);
        }
        catch (ModelNotFoundException $silenced)
        {
           return $this->respond(null, [Errors::RESOURCE_NOT_FOUND], null, ResponseType::NOT_FOUND);
        }
        $paymentProcessor = $this->getProcessorType($processor);
        $response = $paymentProcessor->process($invoice);

        return $this->respond($response->toArray(), [], Messages::INVOICE_PROCESSED);
    }

    /**
     * @param Request $request
     * @param String $processor
     * TODO: think about how to make this generic
     */
    public function callback (Request $request, String $processor)
    {
        $paymentProcessor = $this->getProcessorType($processor);

        $response = $paymentProcessor->callback($request);
        // Else we call the stripe processor
        return $response;
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

    public function subscribe (Request $request, String $processor, int $orderId) : JsonResponse
    {
        try
        {
            $order = Order::findOrFail($orderId);
        }
        catch (ModelNotFoundException $silenced)
        {
            return $this->respond(null, [Errors::RESOURCE_NOT_FOUND], null, ResponseType::NOT_FOUND);
        }
        $paymentProcessor = $this->getProcessorType($processor);
        $response = $paymentProcessor->subscribe($order);

        return $this->respond($response->toArray(), [], Messages::INVOICE_PROCESSED);
    }

    public function unsubscribe (Request $request, int $orderId) : JsonResponse
    {

    }

    private function getProcessorType(String $processor)
    {
        if ($processor == 'paypal')
            return new PaypalProcessor();

        // else return a StripeProcessor()
        return null;
    }
}