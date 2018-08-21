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

        parent::__construct($node);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Failed to verify resource configuration for node #' . $this->node->id)
            ->view('emails.ResourceConfigFailed', [
                'errors' => $this->errors,
                'retryUrl' => $this->retryUrl,
                'node' => $this->node
            ]);
    }
}
