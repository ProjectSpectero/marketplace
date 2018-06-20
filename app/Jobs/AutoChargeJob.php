<?php

namespace App\Jobs;

use App\Constants\InvoiceStatus;
use App\Constants\InvoiceType;
use App\Constants\OrderStatus;
use App\Constants\UserMetaKeys;
use App\Errors\UserFriendlyException;
use App\Invoice;
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
    protected $signature = "invoice:auto-charge";
    protected $description = "Try to charge due invoices from stored Cards or Credit balance.";

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $now = Carbon::now();

        $query = Invoice::where('invoices.type', '!=', InvoiceType::CREDIT)
            ->whereIn('invoices.status', [ InvoiceStatus::UNPAID, InvoiceStatus::PARTIALLY_PAID ])
            ->whereRaw("TIMESTAMPDIFF(DAY, '$now', due_date) <= 0")
            ->select('invoices.*')
            ->distinct()
            ->get();

        $request = new Request();

        foreach ($query as $invoice)
        {
            \Log::debug("Attempting to auto-charge invoice #$invoice->id");
            $user = $invoice->user;

            if ($invoice->status == InvoiceStatus::UNPAID)
            {
                try
                {
                    $request->setUserResolver(function() use ($user)
                    {
                        return $user;
                    });

                    if ($user->credit > 0
                    && $user->credit_currency == $invoice->currency)
                    {
                        \Log::info("$invoice->id has positive balance ($user->credit $user->credit_currency), and currency matches invoice. Attempting to charge $invoice->amount $invoice->currency ...");
                        // OK, he has dollarydoos. Let's go take some.
                        $paymentProcessor = new AccountCreditProcessor($request);
                        $paymentProcessor->enableAutoProcessing();

                        $paymentProcessor->process($invoice);
                    }

                    // Useless call to verify if it's possible to attempt to charge him.
                    UserMeta::loadMeta($user, UserMetaKeys::StripeCustomerIdentifier, true);

                    if (BillingUtils::getInvoiceDueAmount($invoice) > 0)
                    {
                        // This attempts to charge him every day if it fails. We should probably cap it out at x attempts if the card is a dud.
                        // TODO: Implement tracking for non-operational stored payment methods.

                        \Log::info("$invoice->id has an attached user with a saved CC. Attempting to charge $invoice->amount $invoice->currency via Stripe...");
                        $paymentProcessor = new StripeProcessor($request);
                        $paymentProcessor->enableAutoProcessing();

                        $paymentProcessor->process($invoice);
                    }
                }
                catch (UserFriendlyException $exception)
                {
                    \Log::error("A charge attempt (auto-charge) on invoice #$invoice->id has failed: ", [ 'ctx' => $exception ]);
                    // We tried to charge him, but ultimately failed. Let's make him aware of this fact, and fish for payment.
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
