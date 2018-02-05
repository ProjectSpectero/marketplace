<?php


namespace App\Mail;


use App\Libraries\Utility;

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

        return $this->view('emails.WelcomeWithEmailValidation', [
            'verifyUrl' => $url,
        ]);
    }
}