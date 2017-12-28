<?php


namespace App\Libraries;


class Utility
{
    public static function getRandomString () : String
    {
        return md5(uniqid(mt_rand(), true));
    }
}