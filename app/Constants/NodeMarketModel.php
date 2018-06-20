<?php


namespace App\Constants;


class NodeMarketModel extends Holder
{
    const UNLISTED = 'UNLISTED'; //This is the default state
    const ENTERPRISE = 'ENTERPRISE';
    const LISTED_SHARED = 'LISTED_SHARED';
    const LISTED_DEDICATED = 'LISTED_DEDICATED';

    public static function getConstraints ()
    {
        return [
            self::UNLISTED,
            self::LISTED_DEDICATED,
            self::LISTED_SHARED
        ];
    }

    public static function getMarketable ()
    {
        return [
            self::LISTED_SHARED,
            self::LISTED_DEDICATED
        ];
    }
}