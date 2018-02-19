<?php


namespace App\Http\Controllers\V1;


use App\Constants\Errors;
use App\Constants\InvoiceStatus;
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
use App\Libraries\Payment\StripeProcessor;
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
        $this->authorizeResource($invoice, 'invoice.pay');

        if ($invoice->status !== InvoiceStatus::UNPAID)
            throw new UserFriendlyException(Errors::INVOICE_ALREADY_PAID, ResponseType::BAD_REQUEST);

        // TODO: before proceeding further, check that if the invoice has an order associated with it
        // All the desired items of that order are still available to be purchased
        // If not, invoice and order should both be cancelled, with an explanation sent to the user.

        $paymentProcessor = $this->resolveProcessor($processor, $request);
        $rules = $paymentProcessor->getValidationRules('process');
        $this->validate($request, $rules);

        $response = $paymentProcessor->process($invoice);

        return $this->respond($response->toArray(), [], Messages::INVOICE_PROCESSED);
    }

    /**
     * @param Request $request
     * @param String $processor
     * @return JsonResponse
     * @throws FatalException
     */
    public function callback (Request $request, String $processor)
    {
        $paymentProcessor = $this->resolveProcessor($processor, $request);

        $rules = $paymentProcessor->getValidationRules('callback');
        $this->validate($request, $rules);

        return $paymentProcessor->callback($request);
    }

    /**
     * @param Request $request
     * @param int $transactionId (the transaction ID, this is NOT the provider reference)
     * @return JsonResponse
     * @throws UserFriendlyException
     * @throws FatalException
     */
    public function refund (Request $request, int $transactionId) : JsonResponse
    {
        $this->authorizeResource();

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

        $paymentProcessor = $this->resolveProcessor($transaction->payment_processor, $request);
        $response = $paymentProcessor->refund($transaction, $amount);

        return $this->respond($response->toArray(), [], Messages::REFUND_ISSUED);
    }

    public function subscribe (Request $request, String $processor, int $orderId) : JsonResponse
    {
        $order = Order::findOrFail($orderId);
        $this->authorizeResource($order, 'order.subscribe');

        if ($order->due_next->isPast())
            throw new UserFriendlyException(Errors::SERVICE_OVERDUE);

        $paymentProcessor = $this->resolveProcessor($processor);
        $response = $paymentProcessor->subscribe($order);

        return $this->respond($response->toArray(), [], Messages::INVOICE_PROCESSED);
    }

    public function unsubscribe (Request $request, int $orderId) : JsonResponse
    {
        throw new NotSupportedException();
    }

    public function clear(Request $request, String $processor) : JsonResponse
    {
        $paymentProcessor = $this->resolveProcessor($processor);

        $paymentProcessor->clearSavedData();

        return $this->respond(null, [], Messages::SAVED_DATA_CLEARED, ResponseType::NO_CONTENT);
    }

    private function resolveProcessor (String $processor, Request $request) : IPaymentProcessor
    {
        switch (strtolower($processor))
        {
            case strtolower(PaymentProcessor::PAYPAL):
                return new PaypalProcessor($request);

            case strtolower(PaymentProcessor::STRIPE):
                return new StripeProcessor($request);

            default:
                throw new FatalException(Errors::COULD_NOT_RESOLVE_PAYMENT_PROCESSOR);
        }
    }
}