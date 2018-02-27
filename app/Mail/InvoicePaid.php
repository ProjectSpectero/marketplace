<?php

namespace App\Mail;

use App\Invoice;
use App\Libraries\Utility;

class InvoicePaid extends BaseMail
{
    private $invoice;

    /**
     * Create a new message instance.
     *
     * @param Invoice $invoice
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $invoiceUrl = Utility::generateUrl('invoice/' . $this->invoice->id . '/render', 'frontend');

        return $this->subject($this->formatTitle('Thank you for your payment'))
            ->view('PaidInvoice', [
                'invoiceUrl' => $invoiceUrl,
                'transaction' => $this->invoice->transactions->first()
            ]);
    }
}
