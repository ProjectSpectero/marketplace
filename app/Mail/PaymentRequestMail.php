<?php

namespace App\Mail;

use App\Invoice;
use App\Libraries\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PaymentRequestMail extends Mailable
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
        return $this->subject('Payment Request Failed')
            ->view('emails.PaymentRequest', [
                'manualUrl' => $manualUrl,
                'invoice' => $this->invoice
            ]);
    }
}
