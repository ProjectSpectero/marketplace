<?php


namespace App\Mail;


class Welcome extends BaseMail
{
    public function build()
    {
        return $this->view('emails.Welcome');
    }
}