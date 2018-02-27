<?php


namespace App\Mail;


use App\Node;

class NodeVerificationSuccessful extends BaseMail
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
        return $this->subject($this->formatTitle('Node verification succeeded (#' . $this->node->id . ')'))
            ->view('emails.NodeVerificationSuccessful', [
                'node' => $this->node,
                'nodeUrl' => Utility::generateUrl('node/' . $this->node->id, 'frontend')
            ]);
    }
}