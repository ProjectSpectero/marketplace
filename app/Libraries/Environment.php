<?php

namespace App\Libraries;

class Environment
{
    const PRODUCTION = 'PRODUCTION';
    const DEVELOPMENT = 'DEVELOPMENT';
    const LOCAL = 'LOCAL';
    const STAGING = 'STAGING';

    /*
     * @param String $a
     * @param String $b
     * @returns bool
     */
    public static function compare (String $a, String $b)
    {
        return strcasecmp($a, $b) == 0;
    }

    public static function is (string $environment)
    {
        return static::compare(app()->environment(), $environment);
    }

    public static function isProduction ()
    {
        return static::is(self::PRODUCTION);
    }

    public static function isDevelopment ()
    {
        return static::is(self::DEVELOPMENT);
    }

    public static function isLocal ()
    {
        return static::is(self::LOCAL);
    }

    public static function isStaging ()
    {
        return static::is(self::STAGING);
    }

    public static function shouldDiscloseErrors () : bool
    {
        return static::isDevelopment() || static::isLocal();
    }
}