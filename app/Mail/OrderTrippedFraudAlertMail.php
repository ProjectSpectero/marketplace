<?php


namespace App\Mail;


use App\Order;

class OrderTrippedFraudAlertMail extends BaseMail
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
        return $this->subject($this->formatTitle('Your Order #' . $this->order->id . ' could not be automatically verified'))
            ->view('emails.OrderTrippedFraudAlert', [
                'url' => $url,
                'order' => $this->order,
            ]);
    }
}