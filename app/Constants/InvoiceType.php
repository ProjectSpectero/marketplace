<?php

namespace App\Constants;


class InvoiceType extends Holder
{
    const STANDARD = 'STANDARD';
    const CREDIT = 'CREDIT'; // This is what's issued when add-credit is attempted
    const MANUAL = 'MANUAL'; // A manually issued invoice WITHOUT an order_id tagged on.
}