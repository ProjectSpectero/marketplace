<?php

namespace App\Jobs;

use Illuminate\Console\Scheduling\Schedule;

class GeoIPUpdateJob extends BaseJob
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
     * @return string
     */
    public function handle()
    {
        return 'geoipupdate -d ' . base_path() . '/resources/geoip' . ' -f ' . base_path() . '/GeoIP.conf';
    }
}
