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

        $verb = $this->invoice->due_date->isPast() ? 'was' : 'is';

        return $this->subject($this->formatTitle('Payment request for invoice #' . $this->invoice->id))
            ->view('emails.PaymentReminder', [
                'invoice' => $this->invoice,
                'manualUrl' => $manualUrl,
                'verb' => $verb
            ]);
    }
}
