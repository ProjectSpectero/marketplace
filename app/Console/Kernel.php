<?php

namespace App\Console;

use App\Constants\InvoiceStatus;
use App\Constants\OrderStatus;
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
        $schedule->call(function () {
            DB::table('password_reset_tokens')
                ->where('expires', '<=', Carbon::now())
                ->delete();
        })->daily();

        $schedule->call(function() {
            DB::table('partial_auth')
                ->where('expires', '<=', Carbon::now())
                ->delete();
        })->daily();

        $schedule->call(function() {
            $orders = Order::where('status', OrderStatus::ACTIVE)->get();
            foreach ($orders as $order)
            {
                $due_next = Carbon::parse($order->due_next);
                $now = Carbon::now();
                if ( $due_next->diffInDays($now) > env('TERMINATE_AFTER_OVERDUE_DAYS')  )
                {
                    BillingUtils::cancelOrder($order);
                    $lastInvoice = $order->lastInvoice;
                    $lastInvoice->status = OrderStatus::CANCELLED;
                    $lastInvoice->saveOrFail();
                }
                Mail::to($order->user->email)->queue(new OrderTerminated($order));
            }

        })->daily();

        $geoIpUpdate = 'geoipupdate -d ' . base_path() . '/resources/geoip' . ' -f ' . base_path() . '/GeoIP.conf';
        $schedule->exec($geoIpUpdate)
            ->weekly()
            ->sundays()
            ->timezone('America/Los_Angeles');

        $schedule->call(function() {
            $orders = DB::table('orders')
                ->where('status', OrderStatus::ACTIVE)
                ->get();

            foreach ($orders as $order)
            {
                $due_next = Carbon::parse($order->due_next);
                if ($due_next->subDays(env('EARLY_INVOICE_GENERATION_DAYS')) <= Carbon::now()
                    && $order->lastInoivce == InvoiceStatus::PAID)
                        BillingUtils::createInvoice($order, Carbon::parse($order->due_next)->addMonth());
            }
        });
    }
}
