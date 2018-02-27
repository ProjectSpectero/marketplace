<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NodeMail extends BaseMail
{
    protected $retryUrl;

    public function __construct()
    {
        $this->retryUrl = Utility::generateUrl('node/' . $this->node->id . '/verify', 'frontend');
    }
}
