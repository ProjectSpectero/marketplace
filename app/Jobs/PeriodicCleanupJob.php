<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodicCleanupJob extends BaseJob
{
    protected $signature = "core:housekeeping";
    protected $description = "Cleanup expired DB entities of various kind(s).";
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
     */
    public function handle()
    {

        DB::table('password_reset_tokens')
            ->where('expires', '<=', Carbon::now())
            ->delete();

        DB::table('partial_auth')
            ->where('expires', '<=', Carbon::now())
            ->delete();
    }
}
