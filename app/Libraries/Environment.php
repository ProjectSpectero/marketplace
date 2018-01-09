<?php

namespace App\Libraries;

class Environment
{
    const PRODUCTION = 'PRODUCTION';
    const DEVELOPMENT = 'DEVELOPMENT';

    /*
     * @param String $a
     * @param String $b
     * @returns bool
     */
    public static function compare (String $a, String $b)
    {
        return strcasecmp($a, $b) == 0;
    }

    public static function isProduction ()
    {
        return static::compare(app()->environment(), 'production');
    }
}