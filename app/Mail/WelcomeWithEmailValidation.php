<?php


namespace App\Mail;


class WelcomeWithEmailValidation extends BaseMail
{

    private $user;
    private $verifyToken;

    public function __construct($user, $verifyToken)
    {
        $this->user = $user;
        $this->verifyToken = $verifyToken;
    }

    public function build()
    {

        $verifyUrl = env('APP_URL') . '/' . env('API_VERSION') . '/' . $this->user->id . '/' . $this->verifyToken;

        return $this->view('emails.WelcomeWithEmailValidation', [
            'verifyUrl' => $verifyUrl,
        ]);
    }
}