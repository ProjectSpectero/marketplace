<?php


namespace App\Constants;


class TransactionReasons extends Holder
{
    const PAYMENT = 'PAYMENT';
    const SUBSCRIPTION = 'SUBSCRIPTION';
    const REFUND = 'REFUND';
    const PARTIAL_REFUND = 'PARTIAL_REFUND';
}