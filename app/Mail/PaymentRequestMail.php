<?php

namespace App\Mail;

use App\Invoice;
use App\Libraries\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class PaymentRequestMail extends BaseMail
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
        $manualUrl = Utility::generateUrl('invoice/' . $this->invoice->id, 'frontend');
        return $this->subject($this->formatTitle('Automatic Payment Failed'))
            ->view('emails.PaymentRequest', [
                'manualUrl' => $manualUrl,
                'invoice' => $this->invoice
            ]);
    }
}
