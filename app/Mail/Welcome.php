<?php

namespace App\Mail;

class Welcome extends BaseMail
{
    public function build()
    {
        return $this->subject($this->formatTitle("Welcome!"))
            ->view('emails.Welcome');
    }
}