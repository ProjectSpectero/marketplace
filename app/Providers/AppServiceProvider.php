<?php

namespace App\Providers;

use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
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
    }
}
