<?php

namespace App\Mail;

use App\Libraries\Utility;
use App\Node;

class ProxyVerificationFailed extends NodeMail
{

    private $ip;
    private $node;
    private $error;

    /**
     * Create a new message instance.
     *
     * @param Node $node
     * @param String $ip
     * @param String $error
     */
    public function __construct(Node $node, String $ip, String $error = "")
    {

        $this->node = $node;
        $this->ip = $ip;
        $this->error = $error;

        parent::__construct($node);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->formatTitle('Node verification failed (svc: proxy) (#' . $this->node->id . ')'))
            ->view('emails.ProxyVerificationFailed', [
                'node' => $this->node,
                'retryUrl' => $this->retryUrl,
                'error' => $this->error,
                ]);
    }
}
