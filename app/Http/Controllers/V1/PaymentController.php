<?php


namespace App\Http\Controllers\V1;


use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\PaymentProcessor;
use App\Constants\ResponseType;
use App\Errors\NotSupportedException;
use App\Errors\UserFriendlyException;
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
        $invoice = Invoice::findOrFail($invoiceId);

        $paymentProcessor = $this->getProcessorType($processor);
        $response = $paymentProcessor->process($invoice);

        return $this->respond($response->toArray(), [], Messages::INVOICE_PROCESSED);
    }

    /**
     * @param Request $request
     * @param String $processor
     * TODO: think about how to make this generic
     * @return JsonResponse
     * @throws UserFriendlyException
     */
    public function callback (Request $request, String $processor)
    {
        $paymentProcessor = $this->getProcessorType($processor);
        $rules = $paymentProcessor->getCallbackRules();

        $this->validate($request, $rules);
        $response = $paymentProcessor->callback($request);
        // Else we call the stripe processor
        return $response;
    }

    /**
     * @param Request $request
     * @param String $processor
     * @param int $reference (the transaction ID)
     * @return JsonResponse
     * @throws UserFriendlyException
     */
    public function refund (Request $request, String $processor, int $reference) : JsonResponse
    {
        $rules = [
            'amount' => 'required'
        ];

        $this->validate($request, $rules);

        $amount = $request->get('amount');
        $transaction = Transaction::findOrFail($reference);

        $paymentProcessor = $this->getProcessorType($processor);
        $response = $paymentProcessor->refund($transaction, $amount);

        return $this->respond($response->toArray(), [], Messages::REFUND_ISSUED);
    }

    public function subscribe (Request $request, String $processor, int $orderId) : JsonResponse
    {
        $order = Order::findOrFail($orderId);

        $paymentProcessor = $this->getProcessorType($processor);
        $response = $paymentProcessor->subscribe($order);

        return $this->respond($response->toArray(), [], Messages::INVOICE_PROCESSED);
    }

    public function unsubscribe (Request $request, int $orderId) : JsonResponse
    {
        throw new NotSupportedException();
    }

    private function getProcessorType (String $processor)
    {
        return $processor == strtolower(PaymentProcessor::PAYPAL) ? new PaypalProcessor() : null; // else return a stripe processor
    }
}