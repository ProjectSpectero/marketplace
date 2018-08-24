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

        $count = count($query);

        \Log::info("Found $count possible invoice(s) to possibly send payment reminder(s) for.");

        foreach ($query as $invoice)
        {
            // It returns null when it can't find a auto-deduction method.
            if (BillingUtils::resolveAutoDeductionMethod($invoice) == null)
            {
                $user = $invoice->user;

                \Log::info("Sending invoice payment reminder to user #$user->id about invoice #$invoice->id");
                Mail::to($user->email)->queue(new PaymentReminder($invoice));

                $invoice->last_reminder_sent = Carbon::now();
                $invoice->saveOrFail();
            }
        }
    }
}