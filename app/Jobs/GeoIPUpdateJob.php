<?php

namespace App\Jobs;

use Illuminate\Console\Scheduling\Schedule;

class GeoIPUpdateJob extends BaseJob
{
    private $scheduler;
    /**
     * Create a new job instance.
     *
     * @param Schedule $schduler
     */
    public function __construct(Schedule $schduler)
    {
        $this->scheduler = $schduler;
    }

    /**
     * Execute the job.
     *
     * @return string
     */
    public function handle()
    {
        return $this->scheduler->exec('geoipupdate -d ' . base_path() . '/resources/geoip' . ' -f ' . base_path() . '/GeoIP.conf');
    }
}
