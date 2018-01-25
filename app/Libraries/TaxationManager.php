<?php


namespace App\Libraries;


use App\User;

class TaxationManager
{
    // TODO: actually implement these
    public function getPercentage (User $user)
    {
        return 0;
    }

    public function getTaxAmount (Invoice $invoice)
    {
        return 0;
    }
}