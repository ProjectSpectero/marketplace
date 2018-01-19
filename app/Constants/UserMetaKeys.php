<?php

namespace App\Constants;

class UserMetaKeys extends Holder
{
    const Organization = 'organization';
    const AddressLineOne = 'address_line_1';
    const AddressLineTwo = 'address_line_2';
    const City = 'city';
    const State = 'state';
    const PostCode = 'post_code';
    const Country = 'country';
    const PhoneNumber = 'phone_no';
    const PreferredCurrency = 'preferred_currency';
    const TwoFactorSecretKey = 'tfa.secret';
    const TwoFactorEnabled = 'tfa.enabled';
    const OldEmailAddress = 'old_email_address';
    const VerifyToken = 'verify_token';
}