<?php

namespace App\Mail;

use App\Libraries\Utility;
use App\Node;

class ResourceConfigFailed extends BaseMail
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
        $this->retryUrl = Utility::generateUrl('node/resource', 'frontend');
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
