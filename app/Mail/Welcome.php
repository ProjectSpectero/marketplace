<?php


namespace App\Mail;


use Illuminate\Mail\Mailable;

class Welcome extends Mailable
{
    public function build()
    {
        return $this->view('emails.Welcome');
    }
}