<?php

namespace App\Jobs;

use App\Constants\InvoiceStatus;
use App\Constants\OrderStatus;
use App\Constants\UserMetaKeys;
use App\Errors\UserFriendlyException;
use App\Libraries\BillingUtils;
use App\Libraries\Payment\AccountCreditProcessor;
use App\Libraries\Payment\PaypalProcessor;
use App\Libraries\Payment\StripeProcessor;
use App\Mail\PaymentRequestMail;
use App\Order;
use App\UserMeta;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AutoChargeJob extends BaseJob
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
            $invoice = $order->lastInvoice;

            if ($invoice->status == InvoiceStatus::UNPAID)
            {
                try
                {
                    $request->setUserResolver(function() use ($user)
                    {
                        return $user;
                    });

                    if ($user->credit > 0)
                    {
                        $paymentProcessor = new AccountCreditProcessor($request);
                        $paymentProcessor->process($invoice);
                    }

                    $token = UserMeta::loadMeta($user, UserMetaKeys::StripeCardToken, true);

                    $request->replace([
                        'stripeToken' => $token->meta_value
                    ]);

                    if (BillingUtils::getInvoiceDueAmount($invoice) > 0)
                    {
                        $paymentProcessor = new StripeProcessor($request);
                        $paymentProcessor->process($invoice);
                    }
                }
                catch (UserFriendlyException $exception)
                {
                    Mail::to($user->email)->queue(new PaymentRequestMail($invoice, $exception->getMessage()));
                }
                catch (ModelNotFoundException $silenced)
                {
                    // Do nothing actually, this request cannot be sent every time this task runs. We need to separate it out into another job.
                    // User did not have a saved card, and hence nothing to do.
                }
            }
        }
    }
}
