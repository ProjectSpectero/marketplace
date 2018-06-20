<?php

namespace App\Console;

use App\Jobs\AutoChargeJob;
use App\Jobs\GeoIPUpdateJob;
use App\Jobs\InvoicePaymentReminder;
use App\Jobs\OrderTerminationsJob;
use App\Jobs\PeriodicCleanupJob;
use App\Jobs\RecurringInvoiceHandlingJob;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        AutoChargeJob::class,
        GeoIPUpdateJob::class,
        InvoicePaymentReminder::class,
        OrderTerminationsJob::class,
        PeriodicCleanupJob::class,
        RecurringInvoiceHandlingJob::class
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
        $recurringInvoicesJob = new RecurringInvoiceHandlingJob();
        $schedule->call(function() use ($recurringInvoicesJob)
        {
            $recurringInvoicesJob->handle();
        })->daily();

        $autoChargeJob = new AutoChargeJob();
        $schedule->call(function() use ($autoChargeJob)
        {
            $autoChargeJob->handle();
            // 90 seconds to catch up on all the auto charge events fired.
            sleep(90);
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

        $geoIpUpdateJob = new GeoIPUpdateJob();
        $schedule->call(function () use ($geoIpUpdateJob, $schedule)
        {
            $geoIpUpdateJob->handle($schedule);
        })->weekly()->sundays();
    }
}
