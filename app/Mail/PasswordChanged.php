<?php

namespace App\Mail;

use App\Libraries\Utility;

class PasswordChanged extends BaseMail
{
    private $password;
    private $ip;

    /**
     * Create a new message instance.
     *
     * @param String $password
     * @param String $ip
     */
    public function __construct(String $password, String $ip)
    {
        $this->password = $password;
        $this->ip = $ip;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->formatTitle('Your password has been changed'))
            ->view('emails.PasswordChanged', [
                'newPassword' => $this->password,
                'requestIp' => $this->ip,
                'loginUrl' => Utility::generateUrl('login', 'frontend')
            ]);
    }
}
