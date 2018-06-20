<?php


namespace App\Jobs;


use App\Constants\InvoiceStatus;
use App\Constants\UserMetaKeys;
use App\Invoice;
use App\Libraries\BillingUtils;
use App\Mail\PaymentReminder;
use App\UserMeta;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Mail;

class InvoicePaymentReminder extends BaseJob
{
    protected $signature = "invoice:remind";
    protected $description = "Notify user that they have overdue invoices that need paying.";

    /**
     * Create a new job instance.
     *
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
        $overDueBuffer = env('OVERDUE_INVOICE_REMINDER_DAYS', 5);

        $query = Invoice::whereIn('status', [ InvoiceStatus::UNPAID, InvoiceStatus::PARTIALLY_PAID ])
            ->whereNotNull('last_reminder_sent')
            ->whereRaw("TIMESTAMPDIFF(DAY, last_reminder_sent, '$now') >= $overDueBuffer")
            ->get();

        foreach ($query as $invoice)
        {
            $user = $invoice->user;
            $dueAmount = BillingUtils::getInvoiceDueAmount($invoice);

            // Let's verify if user has ample credit to cover the invoice, or if we have a stored payment method.
            // If either are true, there is no point to nagging them for money.

            // User's account has enough credit balance to cover this invoice, let's not bother them.
            if ($user->credit >= $dueAmount && $user->credit_currency == $invoice->currency)
                continue;

            try
            {
                // User has a stored card, we'll auto-charge it when the time comes. Let's not nag them.
                // TODO: Implement tracking for non-operational stored payment methods.

                UserMeta::loadMeta($user, UserMetaKeys::StripeCustomerIdentifier, true);
            }
            catch (ModelNotFoundException $exception)
            {
                \Log::info("Sending invoice payment reminder to user #$user->id about invoice #$invoice->id");
                Mail::to($user->email)->queue(new PaymentReminder($invoice));

                $invoice->last_reminder_sent = Carbon::now();
                $invoice->saveOrFail();
            }
        }
    }
}