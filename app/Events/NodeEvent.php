<?php

namespace App\Events;

use App\Node;

class NodeEvent extends Event
{
    public $node;

    /**
     * Create a new event instance.
     *
     * @param String $type
     * @param Node $node
     * @param array $dataBag
     */
    public function __construct(String $type, Node $node, array $dataBag = [])
    {
        $this->node = $node;
        $this->type = $type;
        $this->dataBag = $dataBag;
    }
}
