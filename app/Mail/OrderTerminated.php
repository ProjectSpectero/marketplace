<?php

namespace App\Mail;


use App\Libraries\Utility;
use App\Order;

class OrderTerminated extends BaseMail
{
    private $order;
    /**
     * Create a new message instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $url = Utility::generateUrl('order/' . $this->order->id, 'frontend');
        return $this->subject($this->formatTitle('Your Order #' . $this->order->id . ' was terminated'))
            ->view('emails.OrderTerminated', [
                'url' => $url
            ]);
    }
}
