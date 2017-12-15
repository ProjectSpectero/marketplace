<?php

namespace App\Constants;

class UserMetaKeys {
    const Street = 'street';
    const City = 'city';
    const PostCode = 'post_code';
    const PhoneNumber = 'phone_no';
    const SecretKey = 'secret';

    static function getConstants()
    {
        $class = new \ReflectionClass(UserMetaKeys::class);
        return $class->getConstants();
    }    
}

