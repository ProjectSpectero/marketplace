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
    public function build()
    {
        return $this->subject('Your email has been changed')
            ->view('emails.EmailChangeOld');
    }
}