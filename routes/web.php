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
        $api->post('user', 'UserController@store');
        $api->get('user/verify/{email}/{token}', 'UserController@verify');
        $api->get('payment/{processor}/callback', 'PaymentController@callback');
        $api->post('password-reset', 'PasswordResetController@generateToken');
        $api->get('password-reset/{token}', 'PasswordResetController@callback');

        $api->post('unauth/node', 'UnauthenticatedNodeController@create');
        $api->post('unauth/node/{id}/{action}', 'UnauthenticatedNodeController@handleConfigPush');

        // Plan specific routes, public by default
        $api->get('plan', 'PlanController@index');
        $api->get('plan/{name}', 'PlanController@show');
    });

    $api->group(['as' => 'AuthRequired', 'middleware' => ['auth:api']], function ($api)
    {
        /** @var \Laravel\Lumen\Routing\Router $api */
        // Group with auth

        // Multifactor Auth Routes
        // Enable and disable have different endpoints because disable needs to pass the TFA filter as well.
        $api->get('auth/multifactor/enable', 'TwoFactorController@enableTwoFactor');
        $api->get('auth/multifactor/disable', [ 'middleware' => 'enforce-tfa', 'uses' => 'TwoFactorController@disableTwoFactor' ]);
        $api->get('auth/multifactor/first-time', [ 'middleware' => 'enforce-tfa', 'uses' => 'TwoFactorController@firstTimeMultiFactor' ]);
        $api->get('auth/multifactor/codes', 'TwoFactorController@showUserBackupCodes');
        $api->get('auth/multifactor/codes/regenerate', 'TwoFactorController@regenerateUserBackupCodes');
        $api->post('auth/impersonate/{id}', 'AuthController@impersonate');

        // Search/Filtering routes
        $api->post('search', 'SearchController@handleSearch');

        // User resource routes, the static "self" route HAS to be before the resource routes
        $api->get('user/self', 'UserController@self');
        $api->put('user/node_key', 'UserController@regenNodeKey');
        \App\Libraries\Utility::defineResourceRoute('user', 'UserController', $api, [], [
            'excluded' =>  \App\Constants\CRUDActions::STORE
        ]);

        // Node resource routes, the static "verify" route HAS to be before the resource routes
        $api->get('node/{id}/verify', 'NodeController@reverify');
        $api->get('node/self[/{action}]', 'NodeController@self');
        \App\Libraries\Utility::defineResourceRoute('node', 'NodeController', $api, []);

        // Invoice resource routes
        $api->get('invoice/self', 'InvoiceController@self');
        \App\Libraries\Utility::defineResourceRoute('invoice', 'InvoiceController', $api, []);

        // Order resource routes
        $api->get('order/self[/{action}]', 'OrderController@self');
        $api->put('order/{id}/accessor', 'OrderController@regenerateAccessor');
        $api->put('order/{id}/fix', 'OrderController@makeOrderDeliverable');
        \App\Libraries\Utility::defineResourceRoute('order', 'OrderController', $api, []);

        // Node group resource routes
        $api->get('node_group/self', 'NodeGroupController@self');
        $api->post('node_group/assign', 'NodeGroupController@assign');
        \App\Libraries\Utility::defineResourceRoute('node_group', 'NodeGroupController', $api, []);

        // Payment processing routes
        $api->post('payment/{processor}/process/{invoiceId}', 'PaymentController@process');
        $api->post('payment/{processor}/subscribe/{invoiceId}', 'PaymentController@subscribe');
        $api->post('payment/refund/{transactionId}', 'PaymentController@refund');
        $api->delete('payment/{processor}/clear', 'PaymentController@clear');

        $api->post('order/cart', 'OrderController@cart');
        \App\Libraries\Utility::defineResourceRoute('transaction', 'TransactionController', $api, []);

        // The Market endpoints now require auth.
        $api->post('market/search', 'MarketplaceController@search');
        $api->get('market/{type}/{id}', 'MarketplaceController@resource');

        \App\Libraries\Utility::defineResourceRoute('engagement', 'EngagementController', $api, []);

        $api->post('promo/code/apply', 'PromoCodeController@apply');
        \App\Libraries\Utility::defineResourceRoute('promo/code', 'PromoCodeController', $api, []);
        \App\Libraries\Utility::defineResourceRoute('promo/group', 'PromoGroupController', $api, []);

        $api->post('credit/invoice', 'InvoiceController@generateCreditInvoice');
        $api->get('credit/max', 'InvoiceController@getMaxCredit');

        $api->get('integrations/support', 'IntegrationsController@support');
    });
});