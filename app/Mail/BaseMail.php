<?php


namespace App\Mail;


use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class BaseMail extends Mailable
    implements ShouldQueue
{

}