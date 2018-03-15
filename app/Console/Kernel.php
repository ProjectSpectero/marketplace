<?php

namespace App\Console;

use App\Constants\InvoiceStatus;
use App\Constants\OrderStatus;
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
        $periodicCleanupJob = new PeriodicCleanupJob();
        $schedule->call(function() use ($periodicCleanupJob) {
            $periodicCleanupJob->handle();
        })->daily();

        $geoIpUpdate = 'geoipupdate -d ' . base_path() . '/resources/geoip' . ' -f ' . base_path() . '/GeoIP.conf';
        $schedule->exec($geoIpUpdate)
            ->weekly()
            ->sundays()
            ->timezone('America/Los_Angeles');

        $recurringInvoicesJob = new RecurringInvoiceHandlingJob();
        $schedule->call(function() use ($recurringInvoicesJob) {
            $recurringInvoicesJob->handle();
        })->daily();

        $orderTerminationsJob = new OrderTerminationsJob();
        $schedule->call(function() use ($orderTerminationsJob){
            $orderTerminationsJob->handle();
        })->everyMinute();
    }
}
