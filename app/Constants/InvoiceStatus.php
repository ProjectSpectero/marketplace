<?php


namespace App\Constants;


class InvoiceStatus extends Holder
{
    const PAID = 'PAID';
    const UNPAID = 'UNPAID';
    const PARTIALLY_PAID = 'PARTIALLY_PAID';
    const PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';
    const REFUNDED = 'REFUNDED';
    const CANCELLED = 'CANCELLED';
    const PROCESSING = 'PROCESSING';

    public static function getPayable () : array
    {
        return [
            self::UNPAID,
            self::PARTIALLY_PAID
        ];
    }
}