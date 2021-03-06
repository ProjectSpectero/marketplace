<?php

namespace App\Providers;

use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if (strtolower($this->app->environment()) != 'production')
        {
            $this->app->register(IdeHelperServiceProvider::class);
        }
    }

    public function boot ()
    {
        Validator::extend('country', 'App\Validators\CountryValidator@validate');
        Validator::extend('equals', 'App\Validators\EqualityValidator@validate');
        Validator::extend('alpha_dash_spaces', 'App\Validators\AlphaDashPlusSpacesValidator@validate');
        Validator::extend('trueboolean', 'App\Validators\TrueBooleanValidator@validate');

        \Queue::failing(function (JobFailed $event)
        {
            // TODO: implement default action when a job fails, perhaps to notify us in Slack?
        });
    }
}
