<?php


namespace App\Mail;


use App\Invoice;
use App\Libraries\Utility;
use App\User;

class CryptoPaymentFailed extends BaseMail
{

    private $invoice;
    private $user;

    /**
     * NodeVerificationFailed constructor.
     * @param Invoice $invoice
     * @param User $user
     * @param string $optionalError
     */
    public function __construct(Invoice $invoice, User $user, string $optionalError = "")
    {
        $this->invoice = $invoice;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject($this->formatTitle('Crypto payment failed (#' . $this->invoice->id . ')'))
            ->view('emails.CryptoPaymentFailed', [
                'invoice' => $this->invoice,
                'user' => $this->user,
                'invoiceUrl' => Utility::generateUrl('invoice/' . $this->invoice->id, 'frontend')
        ]);
    }
}