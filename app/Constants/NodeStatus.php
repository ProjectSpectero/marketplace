<?php


namespace App\Constants;

class NodeStatus extends Holder
{
    const UNCONFIRMED = 'UNCONFIRMED';
    const CONFIRMED = 'CONFIRMED';
    const PENDING_VERIFICATION = 'PENDING_VERIFICATION';
    const PENDING_DELETION = 'PENDING_DELETION';

    public static function getConstraints ()
    {
        return [
            self::UNCONFIRMED, self::CONFIRMED, self::PENDING_VERIFICATION
        ];
    }
}