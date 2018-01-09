<?php


namespace App\Constants;


class Holder
{
    static function getConstants()
    {
        $class = new \ReflectionClass(static::class);
        return $class->getConstants();
    }
}