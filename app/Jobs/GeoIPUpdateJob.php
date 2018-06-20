<?php

namespace App\Jobs;


use Illuminate\Console\Scheduling\Schedule;

class GeoIPUpdateJob extends BaseJob
{

    protected $signature = "geoip:update";
    protected $description = "Update MaxMind GeoIP DB(s)";

    /**
     * Create a new job instance.
     */
    public function __construct ()
    {
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @param Schedule $schedule
     * @return string
     */
    public function handle (Schedule $schedule)
    {
        $command = 'geoipupdate -d ' . base_path() . '/resources/geoip' . ' -f ' . base_path() . '/GeoIP.conf';
        \Log::info("Attempting to update the GeoIP database through MaxMind GeoIP Updater: $command");

        return $schedule->exec($command);
    }
}
