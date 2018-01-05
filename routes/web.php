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

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Libraries\Environment;

$router->group(['prefix' => 'v1', 'namespace' => 'V1'], function($api)
{
    /** @var \Laravel\Lumen\Routing\Router $api */
    $api->group(['as' => 'NoAuthRequired'], function ($api)
    {
        /** @var \Laravel\Lumen\Routing\Router $api */
        // Group without auth
        $api->post('auth', 'AuthController@auth');
        $api->post('auth/refresh', 'AuthController@refreshToken');
        $api->post('auth/multifactor', 'TwoFactorController@verifyToken');
    });

    $api->group(['as' => 'AuthRequired', 'middleware' => ['auth:api', 'cors']], function ($api)
    {
        /** @var \Laravel\Lumen\Routing\Router $api */
        // Group with auth

        // Enable and disable have different endpoints because disable needs to pass the TFA filter as well.
        $api->get('auth/multifactor/enable', 'TwoFactorController@enableTwoFactor');
        $api->get('auth/multifactor/disable', [ 'middleware' => 'enforce-tfa', 'uses' => 'TwoFactorController@disableTwoFactor' ]);
        $api->get('auth/multifactor/first-time', [ 'middleware' => 'enforce-tfa', 'uses' => 'TwoFactorController@firstTimeMultiFactor' ]);
        $api->get('auth/multifactor/codes', 'TwoFactorController@showUserBackupCodes');
        $api->get('auth/multifactor/codes/regenerate', 'TwoFactorController@regenerateUserBackupCodes');

        \App\Libraries\Utility::defineResourceRoute('user', 'UserController', $api, []);
    });

    if (!Environment::isProduction())
    {
        $api->group(['prefix' => 'debug' ], function($api)
        {
            /** @var \Laravel\Lumen\Routing\Router $api */
            $api->get('/test/multifactor-middleware', [ 'middleware' => [ 'auth:api', 'cors', 'enforce-tfa' ], 'uses' => 'DebugController@multiFactorTest' ]);
        });
    }
});