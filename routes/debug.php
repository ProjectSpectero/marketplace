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

use App\Invoice;
use App\Mail\InvoicePaid;

$router->group(['prefix' => 'debug', 'namespace' => 'V1' ], function($api)
{
    /** @var \Laravel\Lumen\Routing\Router $api */
    $api->get('/test', function (\Illuminate\Http\Request $request)
    {

    });
});