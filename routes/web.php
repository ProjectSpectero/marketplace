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
/** @var \Illuminate\Routing\Router $router */
$router->group(['prefix' => 'v1'], function($api)
{
    /** @var \Illuminate\Routing\Router $api */
    $api->group(['as' => 'NoAuthRequired'], function ($api)
    {
        /** @var \Illuminate\Routing\Router $api */
        // Group without authg
        $api->post('auth', 'AuthController@auth');
        $api->post('auth/refresh', 'AuthController@refreshToken');
        $api->post('user', 'UserController@doCreate');
    });

    $api->group(['as' => 'AuthRequired', 'middleware' => ['auth:api', 'cors']], function ($api)
    {
        /** @var \Illuminate\Routing\Router $api */
        $api->post('verify', 'TwoFactorController@verify');
        $api->post('keygen', 'UserController@keygen');
        $api->post('codes', 'UserController@regenerateBackupCodes');
    });

    $api->group(['as' => 'DebugTest', 'middleware' => ['enforce.tfa']], function($api)
    {
        /** @var \Illuminate\Routing\Router $api */
        $api->get('test', function ()
        {
            return 'Success';
        });
    });
});