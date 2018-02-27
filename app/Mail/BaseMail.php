<?php


namespace App\Mail;


use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class BaseMail extends Mailable
    implements ShouldQueue
{
    protected function formatTitle (String $subject) : string
    {
        return env('COMPANY_NAME', 'Spectero') . ': ' . $subject;
    }
}