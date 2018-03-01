<?php

namespace App\Mail;

use App\Libraries\Utility;
use App\Node;

class NodeMail extends BaseMail
{
    protected $retryUrl;

    public function __construct(Node $node)
    {
        $this->retryUrl = Utility::generateUrl('nodes/' . $node->id . '/verify', 'frontend');
    }
}
