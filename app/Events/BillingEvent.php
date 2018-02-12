<?php


namespace App\Events;


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