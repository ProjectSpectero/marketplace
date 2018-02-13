<?php

namespace App\Mail;

use App\Http\Controllers\V1\InvoiceController;
use App\Invoice;
use App\Libraries\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class InvoicePaid extends Mailable
{
    use Queueable, SerializesModels;

    private $invoice;

    /**
     * Create a new message instance.
     *
     * @return void
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
        $invoiceController = new InvoiceController();
        $invoice = $invoiceController->renderInvoice($this->invoice);

        $invoiceUrl = Utility::generateUrl('invoice/' . $this->invoice->id . '/render', 'frontend');

        return $this->subject('Thank you for your purchase')
            ->view('PaidInvoice', [
                'invoiceUrl' => $invoiceUrl
            ]);
    }
}
