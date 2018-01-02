<?php

namespace App\Constants;

class UserMetaKeys
{
    const AddressLineOne = 'address_line_1';
    const AddressLineTwo = 'address_line_2';
    const City = 'city';
    const State = 'state';
    const PostCode = 'post_code';
    const Country = 'country';
    const PhoneNumber = 'phone_no';
    const TwoFactorSecretKey = 'tfa.secret';
    const TwoFactorEnabled = 'tfa.enabled';

    static function getConstants()
    {
        $class = new \ReflectionClass(UserMetaKeys::class);
        return $class->getConstants();
    }    
}

