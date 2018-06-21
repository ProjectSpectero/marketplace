<?php


namespace App\Mail;


use App\Constants\InvoiceType;
use App\Invoice;
use App\Libraries\Utility;

class NewInvoiceGeneratedMail extends BaseMail
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

        $reasonString = '.';

        switch ($this->invoice->type)
        {
            case InvoiceType::CREDIT:
                $reasonString = 'for your request to add account-credit.';
                break;

            case InvoiceType::STANDARD:
                $reasonString = 'for your order #' . $this->invoice->order->id;
                break;
        }

        return $this->subject($this->formatTitle('New Invoice Generated'))
            ->view('emails.NewInvoiceGenerated', [
                'invoice' => $this->invoice,
                'manualUrl' => $manualUrl,
                'reason' => $reasonString
            ]);
    }
}