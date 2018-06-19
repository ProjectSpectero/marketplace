<?php

namespace App\Jobs;


class GeoIPUpdateJob extends BaseJob
{

    protected $signature = "geoip:update";
    protected $description = "Update MaxMind GeoIP DB(s)";

    /**
     * Create a new job instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
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
