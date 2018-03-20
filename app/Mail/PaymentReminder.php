<?php

namespace App\Mail;

use App\Invoice;
use App\Libraries\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PaymentReminder extends BaseMail
{

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
        return $this->subject($this->formatTitle('Overdue Payment'))
            ->view('emails.PaymentReminder', [
                'invoice' => $this->invoice,
                'manualUrl' => $manualUrl
            ]);
    }
}
