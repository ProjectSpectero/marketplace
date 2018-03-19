<?php

namespace App\Jobs;

use phpDocumentor\Reflection\Types\String_;

class GeoIPUpdateJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
