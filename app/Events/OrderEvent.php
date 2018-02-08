<?php


namespace App\Events;


use App\Order;

class OrderEvent extends Event
{
    public $order;

    /**
     * Create a new event instance.
     *
     * @param String $type
     * @param Order $order
     * @param array $dataBag
     */
    public function __construct(String $type, Order $order, array $dataBag = [])
    {
        $this->order = $order;
        $this->type = $type;
        $this->dataBag = $dataBag;
    }
}