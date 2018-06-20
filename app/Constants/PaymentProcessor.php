<?php


namespace App\Constants;


class PaymentProcessor extends Holder
{
    const PAYPAL = 'PAYPAL';
    const STRIPE = 'STRIPE';
    const ACCOUNT_CREDIT = 'ACCOUNT_CREDIT';
    const MANUAL = 'MANUAL';

    public static function getCreditAddAllowedVia () : array
    {
        return [
          self::PAYPAL
        ];
    }
}