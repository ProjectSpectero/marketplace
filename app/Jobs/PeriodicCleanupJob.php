<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodicCleanupJob extends BaseJob
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
