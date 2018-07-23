<?php


namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\InvoiceStatus;
use App\Constants\InvoiceType;
use App\Constants\Messages;
use App\Constants\NodeMarketModel;
use App\Constants\OrderResourceType;
use App\Constants\OrderStatus;
use App\Constants\PaymentProcessor;
use App\Constants\PaymentType;
use App\Constants\ResponseType;
use App\Errors\FatalException;
use App\Errors\NotSupportedException;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\Libraries\BillingUtils;
use App\Libraries\Payment\AccountCreditProcessor;
use App\Libraries\Payment\BasePaymentProcessor;
use App\Libraries\Payment\CryptoProcessor;
use App\Libraries\Payment\IPaymentProcessor;
use App\Libraries\Payment\ManualPaymentProcessor;
use App\Libraries\Payment\PaypalProcessor;
use App\Libraries\Payment\StripeProcessor;
use App\Node;
use App\NodeGroup;
use App\Order;
use App\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends V1Controller
{

    public function __construct()
    {
        $this->resource = 'transaction';
    }

    public function process (Request $request, String $processor, int $invoiceId) : JsonResponse
    {
        /** @var Invoice $invoice */
        $invoice = Invoice::findOrFail($invoiceId);
        $this->authorizeResource($invoice, 'invoice.pay');

        if (! in_array($invoice->status, [ InvoiceStatus::UNPAID, InvoiceStatus::PARTIALLY_PAID ]))
            throw new UserFriendlyException(Errors::INVOICE_STATUS_MISMATCH);

        // Credit-add invoices are ONLY payable with Paypal, we will NOT charge cards to add-credit (lowers liability).
        if (! in_array($processor, BillingUtils::resolveUsableGateways($invoice, $request->user())))
            throw new UserFriendlyException(Errors::GATEWAY_DISABLED_FOR_PURPOSE, ResponseType::FORBIDDEN);

        // The invoice user needs to have a complete billing profile, this call enforces that.
        BillingUtils::compileDetails($invoice->user);

        if ($invoice->type == InvoiceType::STANDARD)
        {
            // Before proceeding further, we need to check that if the invoice has an order associated with it, and all line items are currently available for purchase.
            $order = $invoice->order;

            // This is not supposed to happen, if it does we gotta catch and bail appropriately.
            if ($order == null)
                throw new UserFriendlyException(Errors::PAYMENT_FAILED);

            if (! in_array($order->status, OrderStatus::getPayable()))
                throw new UserFriendlyException(Errors::ORDER_STATUS_MISMATCH);

            // Verify that all the order resources are proper.
            BillingUtils::verifyOrder($order, true);
        }

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
     * @throws UserFriendlyException
     * @throws \Illuminate\Validation\ValidationException
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
     * @throws \Illuminate\Validation\ValidationException
     */
    public function refund (Request $request, int $transactionId) : JsonResponse
    {
        $this->authorizeResource(null, 'transaction.refund');

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
        $paymentProcessor = $this->resolveProcessor($processor, $request);

        $paymentProcessor->clearSavedData();

        return $this->respond(null, [], Messages::SAVED_DATA_CLEARED, ResponseType::NO_CONTENT);
    }

    private function resolveProcessor (String $processor, Request $request) : IPaymentProcessor
    {
        /** @var BasePaymentProcessor $init */
        $init = null;
        switch (strtolower($processor))
        {
            case strtolower(PaymentProcessor::PAYPAL):
                $init = new PaypalProcessor($request);
                break;

            case strtolower(PaymentProcessor::STRIPE):
                $init = new StripeProcessor($request);
                break;

            case strtolower(PaymentProcessor::ACCOUNT_CREDIT):
                $init = new AccountCreditProcessor($request);
                break;

            case strtolower(PaymentProcessor::MANUAL):
                $init = new ManualPaymentProcessor($request);
                break;

            case strtolower(PaymentProcessor::CRYPTO):
                $init = new CryptoProcessor($request);
                break;

            default:
                throw new UserFriendlyException(Errors::UNKNOWN_PAYMENT_PROCESSOR);
        }

        $init->setCaller($this);
        return $init;
    }
}