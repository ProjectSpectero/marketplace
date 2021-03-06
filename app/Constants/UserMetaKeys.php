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
    const TaxIdentification = 'tax_identification';
    const TwoFactorSecretKey = 'tfa.secret';
    const TwoFactorEnabled = 'tfa.enabled';
    const OldEmailAddress = 'old_email_address';
    const VerifyToken = 'verify_token';

    const StripeCustomerIdentifier = 'stripe_customer_identifier';
    const StripeCardToken = 'stripe_card_token';

    // This key represents if the large attempted charge on this stored card was successful or not.
    const StoredCardValid = 'stored_card_valid';
    const StoredCardIdentifier = 'stored_card_identifier';

    // This key tracks an user's first-time authentication, and is default initialized to true
    const LoginCount = 'login_count';

    // This key tracks if the user should be showed the welcome splash screen
    const ShowSplashScreen = 'show_splash_screen';

    // This key tracks whether this user was an "easy signup" user, removed on the first actual password being set.
    const SourcedFromEasySignup = 'user.easy_signup';

    static function getPublicMetaKeys()
    {
        return [
            self::AddressLineOne,
            self::AddressLineTwo,
            self::City,
            self::State,
            self::PostCode,
            self::Country,
            self::PhoneNumber,
            self::TaxIdentification,
            self::Organization,
            self::TwoFactorEnabled,
            self::LoginCount,
            self::ShowSplashScreen
        ];
    }
}