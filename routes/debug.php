<?php
/*
|--------------------------------------------------------------------------
| Debug Routes (Local to env)
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| These routes are NOT registered if environment is production
*/

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'debug', 'namespace' => 'V1' ], function($api)
{
    /** @var \Laravel\Lumen\Routing\Router $api */
    $api->get('/test/hello', [ 'middleware' => [ 'auth:api', 'cors' ], 'uses' => 'DebugController@helloWorld' ]);
});