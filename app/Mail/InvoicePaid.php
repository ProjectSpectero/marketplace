<?php

namespace App\Mail;

use App\Invoice;
use App\Libraries\Utility;
use App\Transaction;

class InvoicePaid extends BaseMail
{
    private $invoice;
    private $transaction;

    /**
     * Create a new message instance.
     *
     * @param Invoice $invoice
     */
    public function __construct(Invoice $invoice, Transaction $transaction)
    {
        $this->invoice = $invoice;
        $this->transaction = $transaction;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $invoiceUrl = Utility::generateUrl('invoices/' . $this->invoice->id, 'frontend');

        return $this->subject($this->formatTitle('Thank you for your payment'))
            ->view('emails.PaidInvoice', [
                'invoiceUrl' => $invoiceUrl,
                'invoice' => $this->invoice,
                'transaction' => $this->transaction
            ]);
    }
}
