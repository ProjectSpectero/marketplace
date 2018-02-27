<?php


namespace App\Mail;


use App\Libraries\Utility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class BaseMail extends Mailable
    implements ShouldQueue
{
    protected $retryUrl;

    /**
     * BaseMail constructor.
     * @param $retryUrl
     */

    public function __construct()
    {
        $this->retryUrl = Utility::generateUrl('node/' . $this->node->id . '/verify', 'frontend');
    }

    protected function formatTitle (String $subject) : string
    {
        return env('COMPANY_NAME', 'Spectero') . ': ' . $subject;
    }
}