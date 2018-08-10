<?php


namespace App\Libraries;


use App\Order;
use App\User;

class FraudCheckManager
{
    public static function stageOne (User $user)
    {
        // TODO: Come up with a simple (yet dynamic) way to define rules for stage one
        return true;
    }

    public static function stageTwo (Order $order)
    {
        // TODO: find a provider of fraud check data and integrate with them for stage 2
        return true;
    }
}