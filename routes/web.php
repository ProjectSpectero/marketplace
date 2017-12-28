<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

/** @var Dingo\Api\Routing\Router $api */
$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api)
{
    /** @var Dingo\Api\Routing\Router $api */

    $api->group(['namespace' => 'App\Http\Controllers'], function ($api)
    {
        // Group without authg
        $api->post('auth', 'AuthController@auth');
        $api->post('auth/refresh', 'AuthController@refreshToken');
        $api->post('register', 'UserController@doCreate');
    });

    $api->group(['namespace' => 'App\Http\Controllers', 'middleware' => ['auth:api', 'cors']], function ($api)
    {
        $api->post('verify', 'TwoFactorController@verify');
        $api->post('keygen', 'UserController@keygen');
        $api->post('codes', 'UserController@regenerateBackupCodes');
    });


    $api->group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers', 'middleware' => ['enforce.tfa']], function($api)
    {
      $api->get('test', function ()
      {
        return 'Success';
      });
    });
});
