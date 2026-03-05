<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Products Configuration
    |--------------------------------------------------------------------------
    |
    | Define your products and their sub-products (platforms) here.
    | Each sub-product needs a unique API key defined in your .env file.
    |
    */

    'products' => [
        'myapp' => [
            'name' => 'My App',
            'description' => 'Example application',
            'icon' => 'fa-mobile-alt',
            'color' => '#4F46E5',
            'sub_products' => [
                'myapp-ios' => [
                    'name' => 'My App iOS',
                    'platform' => 'ios',
                    'icon' => 'fa-apple',
                    'api_key' => env('VITALYTICS_KEY_MYAPP_IOS'),
                    'color' => 'blue',
                ],
                'myapp-android' => [
                    'name' => 'My App Android',
                    'platform' => 'android',
                    'icon' => 'fa-android',
                    'api_key' => env('VITALYTICS_KEY_MYAPP_ANDROID'),
                    'color' => 'green',
                ],
                'myapp-web' => [
                    'name' => 'My App Web',
                    'platform' => 'web',
                    'icon' => 'fa-globe',
                    'api_key' => env('VITALYTICS_KEY_MYAPP_WEB'),
                    'color' => 'purple',
                ],
            ],
        ],
        'another-app' => [
            'name' => 'Another App',
            'description' => 'Another example application',
            'icon' => 'fa-desktop',
            'color' => '#DC2626',
            'sub_products' => [
                'another-app-windows' => [
                    'name' => 'Another App Windows',
                    'platform' => 'windows',
                    'icon' => 'fa-windows',
                    'api_key' => env('VITALYTICS_KEY_ANOTHERAPP_WINDOWS'),
                    'color' => 'indigo',
                ],
                'another-app-macos' => [
                    'name' => 'Another App macOS',
                    'platform' => 'macos',
                    'icon' => 'fa-apple',
                    'api_key' => env('VITALYTICS_KEY_ANOTHERAPP_MACOS'),
                    'color' => 'gray',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Keys (Flat Map)
    |--------------------------------------------------------------------------
    |
    | Flat map of app identifiers to API keys for quick validation.
    | This is auto-generated from the products config above.
    |
    */

    'api_keys' => [
        'myapp-ios' => env('VITALYTICS_KEY_MYAPP_IOS'),
        'myapp-android' => env('VITALYTICS_KEY_MYAPP_ANDROID'),
        'myapp-web' => env('VITALYTICS_KEY_MYAPP_WEB'),
        'another-app-windows' => env('VITALYTICS_KEY_ANOTHERAPP_WINDOWS'),
        'another-app-macos' => env('VITALYTICS_KEY_ANOTHERAPP_MACOS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    */

    'retention_days' => env('VITALYTICS_RETENTION_DAYS', 90),
    'queue' => env('VITALYTICS_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Geolocation
    |--------------------------------------------------------------------------
    */

    'geolocation' => [
        'enabled' => env('VITALYTICS_GEOLOCATION_ENABLED', true),
        'provider' => env('VITALYTICS_GEOLOCATION_PROVIDER', 'ip-api'),
        'cache_ttl' => env('VITALYTICS_GEOLOCATION_CACHE_TTL', 86400),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerts
    |--------------------------------------------------------------------------
    */

    'alerts' => [
        'enabled' => env('VITALYTICS_ALERTS_ENABLED', true),
        'check_interval' => env('VITALYTICS_ALERTS_INTERVAL', 5),
        'cooldown' => env('VITALYTICS_ALERTS_COOLDOWN', 30),
        'slack_webhook' => env('VITALYTICS_SLACK_WEBHOOK'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        'enabled' => env('VITALYTICS_RATE_LIMIT_ENABLED', true),
        'max_requests' => env('VITALYTICS_RATE_LIMIT_MAX', 1000),
        'decay_minutes' => env('VITALYTICS_RATE_LIMIT_DECAY', 1),
    ],

];
