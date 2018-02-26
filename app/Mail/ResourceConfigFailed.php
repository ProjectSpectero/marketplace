<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResourceConfigFailed extends Mailable
{
    use Queueable, SerializesModels;

    private $errors;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Resource configuration failed')
            ->view('emails.ResourceConfigFailed', [
                'errors' => $this->errors
            ]);
    }
}
