<?php


namespace App\Constants;


class Holder
{
    static function getConstants()
    {
        $class = new \ReflectionClass(self::class);
        return $class->getConstants();
    }
}