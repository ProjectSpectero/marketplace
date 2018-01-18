<?php


namespace App\Constants;


class InvoiceStatus extends Holder
{
    const PAID = 'PAID';
    const UNPAID = 'UNPAID';
    const REFUNDED = 'REFUNDED';
    const CANCELLED = 'CANCELLED';
}