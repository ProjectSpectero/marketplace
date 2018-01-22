<?php


namespace App\Mail;


class NodeVerificationFailed extends BaseMail
{

    private $error;

    /**
     * NodeVerificationFailed constructor.
     */
    public function __construct($error)
    {
        $this->error = $error;
    }

    public function build()
    {
        return $this->view('emails.NodeVerificationFailed', ['error' => $this->error]);
    }
}