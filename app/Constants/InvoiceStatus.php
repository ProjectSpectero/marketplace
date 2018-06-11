<?php


namespace App\Constants;


class InvoiceStatus extends Holder
{
    const PAID = 'PAID';
    const UNPAID = 'UNPAID';
    const PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';
    const REFUNDED = 'REFUNDED';
    const CANCELLED = 'CANCELLED';
    const PROCESSING = 'PROCESSING';
}