<?php
/**
 * Created by PhpStorm.
 * User: Gadzev
 * Date: 1/19/2018
 * Time: 2:23 PM
 */

namespace App\Mail;


class EmailChangeOld extends BaseMail
{

    private $newEmail;

    /**
     * EmailChangeOld constructor.
     * @param String $email
     */
    public function __construct(String $email)
    {
        $this->newEmail = $email;
    }

    public function build()
    {
        return $this->subject($this->formatTitle('Your e-mail address has been changed'))
            ->view('emails.EmailChangeOld', [
                'newEmail' => $this->newEmail
            ]);
    }
}