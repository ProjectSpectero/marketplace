<?php


namespace App\Mail;


class NodeVerificationFailed extends BaseMail
{
    public function build()
    {
        return $this->view('emails.NodeVerificationFailed');
    }
}