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
     */
    public function __construct(Node $node, String $error)
    {
        $this->node = $node;
        $this->error = $error;
    }

    public function build()
    {
        return $this->subject('Node verification failed')
            ->view('emails.NodeVerificationFailed', [
                'node' => $this->node,
                'error' => $this->error,
                'retryUrl' => Utility::generateUrl('node/' . $this->node->id . '/verify', 'frontend')
            ]);
    }
}