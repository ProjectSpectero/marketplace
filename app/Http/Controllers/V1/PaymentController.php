<?php


namespace App\Http\Controllers\V1;


use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\PaymentProcessor;
use App\Constants\PaymentType;
use App\Constants\ResponseType;
use App\Errors\FatalException;
use App\Errors\NotSupportedException;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\Libraries\Payment\IPaymentProcessor;
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

        $paymentProcessor = $this->resolveProcessor($processor);
        $response = $paymentProcessor->process($invoice);

        return $this->respond($response->toArray(), [], Messages::INVOICE_PROCESSED);
    }

    /**
     * @param Request $request
     * @param String $processor
     * TODO: think about how to make this generic
     * @return JsonResponse
     * @throws FatalException
     */
    public function callback (Request $request, String $processor)
    {
        $paymentProcessor = $this->resolveProcessor($processor);
        $rules = $paymentProcessor->getCallbackRules();

        $this->validate($request, $rules);
        $response = $paymentProcessor->callback($request);
        // Else we call the stripe processor
        return $response;
    }

    /**
     * @param Request $request
     * @param int $transactionId (the transaction ID, this is NOT the provider reference)
     * @return JsonResponse
     * @throws UserFriendlyException
     */
    public function refund (Request $request, int $transactionId) : JsonResponse
    {
        $rules = [
            'amount' => 'required'
        ];

        $this->validate($request, $rules);

        $transaction = Transaction::findOrFail($transactionId);
        $amount = $request->has('amount') ? $request->get('amount') : $transaction->amount;

        if ($amount > $transaction->amount)
            throw new UserFriendlyException(Errors::REFUND_AMOUNT_IS_BIGGER_THAN_TRANSACTION);

        if ($transaction->type !== PaymentType::CREDIT)
            throw new UserFriendlyException(Errors::COULD_NOT_REFUND_NON_CREDIT_TXN);

        $paymentProcessor = $this->resolveProcessor($transaction->payment_processor);
        $response = $paymentProcessor->refund($transaction, $amount);

        return $this->respond($response->toArray(), [], Messages::REFUND_ISSUED);
    }

    public function subscribe (Request $request, String $processor, int $orderId) : JsonResponse
    {
        $order = Order::findOrFail($orderId);

        $paymentProcessor = $this->resolveProcessor($processor);
        $response = $paymentProcessor->subscribe($order);

        return $this->respond($response->toArray(), [], Messages::INVOICE_PROCESSED);
    }

    public function unsubscribe (Request $request, int $orderId) : JsonResponse
    {
        throw new NotSupportedException();
    }

    private function resolveProcessor (String $processor) : IPaymentProcessor
    {
        switch ($processor)
        {
            case strtolower(PaymentProcessor::PAYPAL):
                return new PaypalProcessor();

            case strtolower(PaymentProcessor::STRIPE):
                return null;

            default:
                throw new FatalException(Errors::COULD_NOT_RESOLVE_PAYMENT_PROCESSOR);
        }
    }
}