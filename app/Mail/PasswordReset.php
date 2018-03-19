<?php


namespace App\Mail;


use App\Libraries\Utility;
use App\PasswordResetToken;

class PasswordReset extends BaseMail
{
    private $token;
    private $ip;

    /**
     * PasswordReset constructor.
     * @param PasswordResetToken $token
     * @param String $ip
     */
    public function __construct(PasswordResetToken $token, String $ip)
    {
        $this->token = $token;
        $this->ip = $ip;
    }

    public function build()
    {
        return $this->subject($this->formatTitle('Your password has been reset'))
            ->view('emails.PasswordReset', [
                'requesterIP' => $this->ip,
                'resetUrl' => Utility::generateUrl('password-reset/' . $this->token->token, 'frontend'),
                'expires' => $this->token->expires
            ]);
    }
}