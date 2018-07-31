<?php

require_once __DIR__.'/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__.'/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/
if (!function_exists('app_path'))
{
    function app_path($path = '')
    {
        return app('path').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if ( ! function_exists('config_path'))
{
    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->configure('auth');
$app->configure('database');
$app->configure('cache');
$app->configure('queue');
$app->configure('broadcasting');
$app->configure('resources');
$app->configure('search');
$app->configure('pagination');
$app->configure('paypal');
$app->configure('services');
$app->configure('mail');
$app->configure('plans');
$app->configure('pools');
$app->configure('logging');

$app->withFacades();

$app->withEloquent();


/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    'Nord\Lumen\Cors\CorsMiddleware',
]);

 $app->routeMiddleware([
     'auth' => App\Http\Middleware\Authenticate::class,
     'enforce-tfa' => App\Http\Middleware\EnforceTwoFactorVerification::class,
 ]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

    $app->register(Illuminate\Redis\RedisServiceProvider::class);
    $app->register(App\Providers\AppServiceProvider::class);
    $app->register(App\Providers\AuthServiceProvider::class);
    $app->register(App\Providers\EventServiceProvider::class);
    $app->register(Flipbox\LumenGenerator\LumenGeneratorServiceProvider::class);
    $app->register(Laravel\Passport\PassportServiceProvider::class);
    $app->register(Dusterio\LumenPassport\PassportServiceProvider::class);
    Dusterio\LumenPassport\LumenPassport::routes($app);
    $app->register(Silber\Bouncer\BouncerServiceProvider::class);
    $app->register(Srmklive\PayPal\Providers\PayPalServiceProvider::class);
    $app->register(\Illuminate\Mail\MailServiceProvider::class);
    $app->register(Nord\Lumen\Cors\CorsServiceProvider::class);
    $app->register(Cartalyst\Stripe\Laravel\StripeServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Class Aliases
|--------------------------------------------------------------------------
|
| This array of class aliases will be registered when this application
| is started. However, feel free to register as many as you wish as
| the aliases are "lazy" loaded so they don't hinder performance.
|
*/

if (!class_exists('Bouncer'))
{
    class_alias(Silber\Bouncer\BouncerFacade::class, 'Bouncer');
}

if (!class_exists('PayPal'))
{
    class_alias(Srmklive\PayPal\Facades\PayPal::class, 'PayPal');
}

$app->alias('mailer', \Illuminate\Contracts\Mail\Mailer::class);
$app->alias('Stripe', Cartalyst\Stripe\Laravel\Facades\Stripe::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router)
{
    require __DIR__.'/../routes/web.php';
    if (! \App\Libraries\Environment::isProduction())
        require __DIR__.'/../routes/debug.php';
});

return $app;
