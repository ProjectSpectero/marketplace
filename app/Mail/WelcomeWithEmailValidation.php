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

        $url = Utility::generateUrl('verify/' . $this->user->email . '/' . $this->verifyToken, 'frontend');

        return $this->subject('Welcome')
            ->view('emails.WelcomeWithEmailValidation', [
                'verifyUrl' => $url,
            ]);
    }
}