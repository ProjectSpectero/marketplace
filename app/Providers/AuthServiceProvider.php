<?php

namespace App\Providers;

use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {
            if ($request->input('api_token')) {
                return User::where('api_token', $request->input('api_token'))->first();
            }
        });

        \Dusterio\LumenPassport\LumenPassport::allowMultipleTokens();
        Passport::tokensExpireIn(Carbon::now()->addMinutes(env('TOKEN_EXPIRY', 10)));
        Passport::refreshTokensExpireIn(Carbon::now()->addMinutes(env('REFRESH_TOKEN_EXPIRY', 20)));
    }
}
