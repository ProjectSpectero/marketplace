<?php

namespace App\Console;

use App\Constants\InvoiceStatus;
use App\Constants\OrderStatus;
use App\Jobs\AutoChargeJob;
use App\Jobs\GeoIPUpdateJob;
use App\Jobs\InvoicePaymentReminder;
use App\Jobs\OrderTerminationsJob;
use App\Jobs\PeriodicCleanupJob;
use App\Jobs\RecurringInvoiceHandlingJob;
use App\Libraries\BillingUtils;
use App\Mail\OrderTerminated;
use App\Order;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /*
         * Take NOTE: the order here matters, as do the sleep calls (to allow for the listener/event processor to catch up)
         */
        $autoChargeJob = new AutoChargeJob();
        $schedule->call(function() use ($autoChargeJob)
        {
            $autoChargeJob->handle();
            // 90 seconds to catch up on all the auto charge events fired.
            sleep(90);
        })->daily();

        $recurringInvoicesJob = new RecurringInvoiceHandlingJob();
        $schedule->call(function() use ($recurringInvoicesJob)
        {
            $recurringInvoicesJob->handle();
        })->daily();

        $invoiceReminderJob = new InvoicePaymentReminder();
        $schedule->call(function () use ($invoiceReminderJob)
        {
           $invoiceReminderJob->handle();
        })->daily();

        $orderTerminationsJob = new OrderTerminationsJob();
        $schedule->call(function() use ($orderTerminationsJob)
        {
            $orderTerminationsJob->handle();
        })->daily();

        $periodicCleanupJob = new PeriodicCleanupJob();
        $schedule->call(function() use ($periodicCleanupJob) {
            $periodicCleanupJob->handle();
        })->daily();

        $geoIpUpdateJob = new GeoIPUpdateJob($schedule);
        $schedule->call(function () use ($geoIpUpdateJob)
        {
            $geoIpUpdateJob->handle();
        })->weekly()
            ->sundays();
    }
}
