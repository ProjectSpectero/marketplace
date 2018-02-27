<?php


namespace App\Mail;


use App\Libraries\Utility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class BaseMail extends Mailable
    implements ShouldQueue
{
    /**
     * BaseMail constructor.
     * @param $retryUrl
     */

    protected function formatTitle (String $subject) : string
    {
        return env('COMPANY_NAME', 'Spectero') . ': ' . $subject;
    }
}