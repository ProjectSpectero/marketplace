<?php


namespace App\Mail;


use App\Libraries\Utility;
use App\Node;

class NodeVerificationFailed extends BaseMail
{

    private $error;
    private $node;

    /**
     * NodeVerificationFailed constructor.
     * @param Node $node
     * @param String $error
     */
    public function __construct(Node $node, String $error)
    {
        $this->node = $node;
        $this->error = $error;
    }

    public function build()
    {
        return $this->subject($this->formatTitle('Node verification failed (#' . $this->node->id . ')'))
            ->view('emails.NodeVerificationFailed', [
                'node' => $this->node,
                'error' => $this->error,
                'retryUrl' => $this->retryUrl
            ]);
    }
}