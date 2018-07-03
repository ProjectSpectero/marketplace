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
     * @throws UserFriendlyException <-- Technically possible, but the status check is duplicated. Which means this is for fluff only.
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

        $count = count($query);
        \Log::info("Found $count possible order(s) to attempt to automatically charge.");

        /** @var Invoice $invoice */
        foreach ($query as $invoice)
        {
            \Log::debug("Verifying if auto-charge is possible for Invoice #$invoice->id");
            BillingUtils::attemptToChargeIfPossible($invoice);
        }
    }
}
