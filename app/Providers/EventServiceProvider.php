<?php

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\UserEvent' => [
            'App\Listeners\UserEventListener',
        ],
        'App\Events\NodeEvent' => [
            'App\Listeners\NodeEventListener'
        ],
        'App\Events\FraudCheckEvent' => [
            'App\Listeners\FraudCheckEventListener'
        ]
    ];
}
