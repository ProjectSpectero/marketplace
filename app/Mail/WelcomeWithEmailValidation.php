<?php


namespace App\Mail;


use Illuminate\Mail\Mailable;

class WelcomeWithEmailValidation extends Mailable
{
    public function build()
    {
        return $this->view('emails.Welcome');
    }
}