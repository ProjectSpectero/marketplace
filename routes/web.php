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

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {
    $api->group(['prefix' => 'oauth'], function ($api) {
        $api->post('token', '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');
    });

    $api->group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers'], function ($api) {
        $api->post('auth', 'AuthController@auth');
        $api->post('register', 'AuthController@register');
    });

    $api->group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers', 'middleware' => ['auth:api', 'cors']], function ($api) {
        $api->post('refresh', 'AuthController@refreshToken');
        $api->post('verify', 'AuthController@verify');
        $api->post('keygen', 'AuthController@keygen');
    });

    $api->group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers', 'middleware' => ['verify']], function($api) {
      $api->get('test', function () {
        return 'Success';
      });
    });
});
