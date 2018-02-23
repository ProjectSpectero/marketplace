<?php


namespace App\Libraries;


use App\Order;

class FraudCheckManager
{
    public static function verify (Order $order)
    {
        // TODO: find a provider of fraud check data and integrate with them
        return true;
    }
}