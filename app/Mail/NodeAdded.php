<?php


namespace App\Mail;


use App\Libraries\Utility;
use App\Node;

class NodeAdded extends BaseMail
{
    private $node;

    /**
     * NodeVerificationFailed constructor.
     * @param Node $node
     */
    public function __construct(Node $node)
    {
        $this->node = $node;
    }

    public function build()
    {
        return $this->subject($this->formatTitle('New node added (#' . $this->node->id . ')'))
            ->view('emails.NodeAdded', [
                'node' => $this->node,
                'nodeUrl' => Utility::generateUrl('node/' . $this->node->id, 'frontend')
            ]);
    }
}