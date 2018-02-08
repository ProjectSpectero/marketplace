<?php
/**
 * Created by PhpStorm.
 * User: Gadzev
 * Date: 1/19/2018
 * Time: 2:23 PM
 */

namespace App\Mail;


use App\Libraries\Utility;
use App\User;

class EmailChangeNew extends BaseMail
{


    private $user;
    private $verifyToken;

    /**
     * EmailChangeNew constructor.
     */
    public function __construct(User $user, String $verifyToken)
    {
        $this->user = $user;
        $this->verifyToken = $verifyToken;
    }

    public function build()
    {
        $url = Utility::generateUrl('verify/' . $this->user->email . '/' . $this->verifyToken, 'frontend');

        return $this->subject("Email successfully changed")
            ->view('emails.EmailChangeNew', [
                'verifyUrl' => $url,
            ]);
    }
}