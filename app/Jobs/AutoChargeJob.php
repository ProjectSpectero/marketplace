<?php

namespace App\Jobs;

use App\Constants\InvoiceStatus;
use App\Constants\OrderStatus;
use App\Constants\UserMetaKeys;
use App\Errors\UserFriendlyException;
use App\Libraries\Payment\StripeProcessor;
use App\Mail\EmailChangeNew;
use App\Mail\PaymentRequestMail;
use App\Order;
use App\UserMeta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AutoChargeJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $query = Order::join('order_line_items', 'orders.id', '=', 'order_line_items.order_id')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select('orders.*')
            ->where('orders.status', '=', OrderStatus::ACTIVE)
            ->where('orders.due_next', '<=', Carbon::now())
            ->distinct()
            ->get();

        $request = new Request();

        foreach ($query as $order)
        {
            $user = $order->user;
            if ($order->lastInvoice->status == InvoiceStatus::UNPAID)
            {
                if ($token = UserMeta::loadMeta($user, UserMetaKeys::StripeCardToken)->first())
                {
                    $request->replace([
                        'user' => $user,
                        'stripeToken' => $token->meta_value
                    ]);

                    try
                    {
                        $paymentProcessor = new StripeProcessor($request);
                        $paymentProcessor->process($order->lastInvoice);
                    }
                    catch (UserFriendlyException $silenced)
                    {

                    }
                }
                else
                {
                    \Mail::to($user->email)->queue(new PaymentRequestMail());
                }
            }

        }

    }
}
