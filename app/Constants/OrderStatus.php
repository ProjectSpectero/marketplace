<?php


namespace App\Constants;


class OrderStatus extends Holder
{
    const PENDING = 'PENDING';
    const AUTOMATED_FRAUD_CHECK = 'AUTOMATED_FRAUD_CHECK';
    const MANUAL_FRAUD_CHECK = 'MANUAL_FRAUD_CHECK';
    const ACTIVE = 'ACTIVE';
    const CANCELLED = 'CANCELLED';
}