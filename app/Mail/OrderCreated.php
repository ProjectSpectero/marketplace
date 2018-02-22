<?php

namespace App\Mail;

use App\Libraries\Utility;
use App\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderCreated extends Mailable
{
    use Queueable, SerializesModels;

    private $order;

    /**
     * Create a new message instance.
     *
     * @return void
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
        return $this->subject('Thank you for your order')
            ->view('emails.OrderCreated', [
                'url' => $url
            ]);
    }
}
