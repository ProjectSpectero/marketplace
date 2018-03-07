<?php

namespace App\Libraries;

use App\Order;
use App\User;

class TaxationManager
{
    // TODO: actually implement these
    public static function getPercentage (User $user)
    {
        return 0;
    }

    public static function getTaxAmount (Order $order, float $subTotals)
    {
        return 0;
    }
}