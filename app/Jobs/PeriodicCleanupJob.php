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
        \Log::info("Cleaning up stale tokens/resources.");

        $prtCount = DB::table('password_reset_tokens')
            ->where('expires', '<=', Carbon::now())
            ->delete();

        \Log::info("$prtCount expired password reset token(s) were removed.");

        $paCount = DB::table('partial_auth')
            ->where('expires', '<=', Carbon::now())
            ->delete();

        \Log::info("$paCount expired partial-auth (two-factor) token(s) were removed.");

        $oatCount = DB::table('oauth_access_tokens')
            ->where('expires_at', '<=', Carbon::now('UTC'))
            ->delete();

        \Log::info("$oatCount expired access token(s) (oAuth) were removed.");
    }
}
