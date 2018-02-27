<?php

namespace App\Mail;

use App\Libraries\Utility;
use App\Node;

class ResourceConfigFailed extends NodeMail
{
    private $errors;
    private $node;

    /**
     * Create a new message instance.
     *
     * @param Node $node
     * @param array $errors
     */
    public function __construct(Node $node, array $errors)
    {
        $this->errors = $errors;
        $this->node = $node;
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
                'errors' => $this->errors,
                'retryUrl' => $this->retryUrl
            ]);
    }
}
