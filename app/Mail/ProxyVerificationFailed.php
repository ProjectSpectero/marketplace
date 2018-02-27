<?php

namespace App\Mail;

use App\Libraries\Utility;
use App\Node;

class ProxyVerificationFailed extends BaseMail
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
        $this->retryUrl = Utility::generateUrl('node/proxy', 'frontend');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // TODO: dress this up to be a proper, hierarchical node related error message. Provide link to re-verify in there to retry.

        return $this->subject($this->formatTitle('Node verification failed (svc: proxy) (#' . $this->node->id . ')'))
            ->view('emails.ProxyVerificationFailed', [
                'retryUrl' => $this->retryUrl
                ]);
    }
}
