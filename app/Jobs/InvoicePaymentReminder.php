<?php


namespace App\Jobs;


use App\Constants\InvoiceStatus;
use App\Invoice;
use App\Mail\PaymentRequestMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class InvoicePaymentReminder extends BaseJob
{
    /**
     * Create a new job instance.
     *
     */
    public function __construct()
    {

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

        $query = Invoice::where('status', InvoiceStatus::UNPAID)
            ->whereNotNull('last_reminder_sent')
            ->whereRaw("TIMESTAMPDIFF(DAY, last_reminder_sent, '$now') >= $overDueBuffer")
            ->get();

        dd($query);

        foreach ($query as $invoice)
        {
            Mail::to($invoice->user->email)->queue(new PaymentRequestMail($invoice));
            $invoice->last_reminder_sent = Carbon::now();
            $invoice->saveOrFail();
        }
    }
}