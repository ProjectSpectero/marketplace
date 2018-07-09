<?php

namespace App\Mail;

use App\Libraries\Utility;

class Welcome extends BaseMail
{
    public function build()
    {
        return $this->subject($this->formatTitle("Welcome!"))
            ->view('emails.Welcome', [
                'loginUrl' => Utility::generateUrl('login', 'frontend')
        ]);
    }
}