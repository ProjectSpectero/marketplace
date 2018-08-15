<?php


namespace App\Constants;


class NodeStatus extends Holder
{
    const UNCONFIRMED = 'UNCONFIRMED';
    const CONFIRMED = 'CONFIRMED';
    const PENDING_VERIFICATION = 'PENDING_VERIFICATION';
    const PENDING_DELETION = 'PENDING_DELETION';
}