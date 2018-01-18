<?php


namespace App\Mail;


class WelcomeWithEmailValidation extends BaseMail
{
    public function build()
    {
        return $this->view('emails.Welcome');
    }
}