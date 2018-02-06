<?php


namespace App\Events;


use App\Transaction;

class BillingEvent extends Event
{
    public $data;
    public function __construct(String $type, $object, array $dataBag = [])
    {
        $this->type = $type;
        $this->dataBag = $dataBag;
        $this->data = $object;
    }
}