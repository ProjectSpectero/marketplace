<?php


namespace App\Libraries\Payment;
use App\Constants\Errors;
use App\Constants\Events;
use App\Constants\InvoiceStatus;
use App\Constants\ResponseType;
use App\Errors\UserFriendlyException;
use App\Events\BillingEvent;
use Illuminate\Http\Request;
use App\Http\Controllers\V1\V1Controller;
use App\Invoice;
use App\Libraries\BillingUtils;
use App\Libraries\Utility;
use App\Transaction;

abstract class BasePaymentProcessor implements IPaymentProcessor
{
    protected $caller;
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param IPaymentProcessor $processor
     * @param Invoice $invoice
     * @param Float $amount
     * @param Float $fee
     * @param String $transactionId
     * @param String $transactionType
     * @param String $reason
     * @param String $rawData
     * @return Transaction
     * @throws \Throwable
     */
    public function addTransaction (IPaymentProcessor $processor, Invoice $invoice,
                                    Float $amount, Float $fee,
                                    String $transactionId, String $transactionType,
                                    String $reason, String $rawData,
                                    int $originalTransactionId = -1) : Transaction
    {
        $transaction = new Transaction();
        $transaction->invoice_id = $invoice->id;
        $transaction->payment_processor = $processor->getName();
        $transaction->reference = $transactionId;
        $transaction->type = $transactionType;
        $transaction->reason = $reason;
        $transaction->amount = $amount;
        $transaction->fee = $fee;
        $transaction->currency = $invoice->currency;
        $transaction->raw_response = $rawData;

        if ($originalTransactionId != -1)
            $transaction->original_transaction_id = $originalTransactionId;

        $transaction->saveOrFail();

        $invoice->status = InvoiceStatus::PROCESSING;
        $invoice->saveOrFail();

        event(new BillingEvent(Events::BILLING_TRANSACTION_ADDED, $transaction));
        return $transaction;
    }

    /**
     * @param Invoice $invoice
     * @return String
     */
    public function getInvoiceDescription (Invoice $invoice) : String
    {
        $companyName = env('COMPANY_NAME', 'Spectero');
        return $companyName . ' Invoice #' . $invoice->id;
    }

    /**
     * @param Invoice $invoice
     * @return mixed
     * @throws UserFriendlyException
     */
    public function getDueAmount (Invoice $invoice)
    {
        $lowestAllowedAmount = env('LOWEST_ALLOWED_PAYMENT', 5);

        $amount = BillingUtils::getInvoiceDueAmount($invoice);

        if ($amount <= 0)
            throw new UserFriendlyException(Errors::INVOICE_ALREADY_PAID, ResponseType::BAD_REQUEST);

        if ($amount < $lowestAllowedAmount)
            throw new UserFriendlyException(Errors::INVOICE_DUE_IS_LOWER_THAN_LOWEST_THRESHOLD, ResponseType::BAD_REQUEST);

        return $amount;
    }

    /**
     * @param Invoice $invoice
     * @return string
     */
    public function getPartialInvoiceId (Invoice $invoice)
    {
        return $invoice->id . '-' . Utility::getRandomString(1);
    }

    public function getMajorInvoiceIdFromPartialId (String $id)
    {
        if (is_numeric($id))
            return $id;

        list ($major, $minor) = explode('-', $id);

        return $major;
    }

    public function setCaller (V1Controller $controller)
    {
        $this->caller = $controller;
    }
}