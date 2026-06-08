<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route
    |--------------------------------------------------------------------------
    |
    | The path the status page and JSON endpoint are served from. The same path
    | returns JSON when the request asks for it (Accept: application/json or
    | ?json=1) and the HTML status page otherwise.
    |
    */

    'route' => env('HEALTH_UI_ROUTE', 'health'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to the health route. The endpoint can expose internal
    | details, so protect it in production — e.g. a signed-URL or token
    | middleware, or restrict it to an internal network.
    |
    */

    'middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Result caching
    |--------------------------------------------------------------------------
    |
    | Cache the report so frequent monitor polls don't run every check on every
    | hit. Set ttl to 0 to disable.
    |
    */

    'cache' => [
        'ttl' => (int) env('HEALTH_UI_CACHE_TTL', 0),
        'store' => env('HEALTH_UI_CACHE_STORE'),
        'key' => 'health-ui.report',
    ],

    /*
    |--------------------------------------------------------------------------
    | Checks
    |--------------------------------------------------------------------------
    */

    'checks' => [

        'database' => [
            'enabled' => true,
            'connection' => null,
        ],

        'cache' => [
            'enabled' => true,
            'store' => null,
        ],

        'disk_space' => [
            'enabled' => true,
            'path' => null, // null = base_path()
            'warning_threshold' => 80,
            'failure_threshold' => 90,
        ],

        'debug_mode' => [
            'enabled' => true,
        ],

        'http' => [
            'enabled' => false,
            'endpoints' => [
                // ['name' => 'Payments API', 'url' => 'https://api.example.com/health', 'timeout' => 5],
            ],
        ],

        'queue_failed_jobs' => [
            'enabled' => false,
            'connection' => null,
            'table' => 'failed_jobs',
            'warning_threshold' => 1,
            'failure_threshold' => 25,
        ],

        'schedule' => [
            'enabled' => false,
            'key' => 'health-ui:schedule-heartbeat',
            'store' => null,
            'max_age_minutes' => 5,
        ],

        'migrations' => [
            'enabled' => false,
        ],

        'certificates' => [
            'enabled' => false,
            'warning_days' => 14,
            'failure_days' => 3,
            'timeout' => 5,
            'hosts' => [
                // 'example.com',
            ],
        ],

    ],

];
