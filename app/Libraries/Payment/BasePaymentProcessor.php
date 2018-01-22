<?php


namespace App\Libraries\Payment;
use App\Invoice;
use App\Transaction;

abstract class BasePaymentProcessor implements IPaymentProcessor
{
    /**
     * @param IPaymentProcessor $processor
     * @param Invoice $invoice
     * @param Float $amount
     * @param String $transactionId
     * @param String $transactionType
     * @param String $reason
     * @return Transaction
     * @throws \Throwable
     */
    public function addTransaction (IPaymentProcessor $processor, Invoice $invoice,
                                    Float $amount, String $transactionId,
                                    String $transactionType, String $reason) : Transaction
    {
        $transaction = new Transaction();
        $transaction->invoice_id = $invoice->id;
        $transaction->payment_processor = $processor->getName();
        $transaction->reference = $transactionId;
        $transaction->type = $transactionType;
        $transaction->reason = $reason;
        $transaction->amount = $amount;
        $transaction->currency = $invoice->currency;

        $transaction->saveOrFail();

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
     * @param IPaymentProcessor $processor
     * @param Invoice $invoice
     * @param Transaction $transaction
     * @param String $type
     * @return string
     */
    public function getUrl (IPaymentProcessor $processor, Invoice $invoice, Transaction $transaction, String $type) : string
    {
        // TODO: build this
        return "";
    }
}