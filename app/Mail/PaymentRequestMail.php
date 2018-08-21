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
    private $reason;

    /**
     * Create a new message instance.
     *
     * @param Invoice $invoice
     * @param String|null $reason
     */
    public function __construct(Invoice $invoice, String $reason = null)
    {
        $this->invoice = $invoice;
        $this->reason = $reason;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $manualUrl = Utility::generateUrl('invoice/' . $this->invoice->id, 'frontend');
        return $this->subject($this->formatTitle('Automatic payment failed for invoice #' . $this->invoice->id))
            ->view('emails.PaymentRequest', [
                'manualUrl' => $manualUrl,
                'invoice' => $this->invoice,
                'reason' => $this->reason
            ]);
    }
}
