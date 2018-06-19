<?php


namespace App\Constants;


class OrderResourceType extends Holder
{
    const NODE = 'NODE';
    const NODE_GROUP = 'NODE_GROUP';
    const ENTERPRISE = 'ENTERPRISE';

    // TODO: Add ent here once we have ent autoprov going
    public static function getOrderable () : array
    {
        return [
          self::NODE_GROUP, self::NODE
        ];
    }
}