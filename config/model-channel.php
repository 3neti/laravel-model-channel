<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Channel validation rules / existing config...
    |--------------------------------------------------------------------------
    */

    'rules' => [
        'mobile' => [
            'required',
            'phone:PH,mobile', // String format instead of object for serialization
        ],
        'webhook' => ['required', 'url'],
        'telegram' => ['required', 'string', 'regex:/^-?\d+$/'], // Chat ID (can be negative for groups)
        'whatsapp' => ['required', 'string'],
        'viber' => ['required', 'string'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lookup Cache
    |--------------------------------------------------------------------------
    |
    | This cache is intentionally focused on channel-to-model lookup methods
    | such as findByMobile() and findByWebhook(). It does not cache regular
    | accessor reads like getMobileChannel(); eager loading remains the
    | preferred optimization for those.
    |
    */

    'cache' => [
        'enabled' => env('MODEL_CHANNEL_CACHE_ENABLED', false),
        'store' => env('MODEL_CHANNEL_CACHE_STORE', null),
        'ttl' => (int)env('MODEL_CHANNEL_CACHE_TTL', 600), // seconds
        'prefix' => env('MODEL_CHANNEL_CACHE_PREFIX', 'model-channel'),

        /*
        |--------------------------------------------------------------------------
        | Cacheable channels
        |--------------------------------------------------------------------------
        |
        | Only these channel lookups will use the cache layer.
        |
        */
        'channels' => [
            'mobile',
            'webhook',
        ],

        /*
        |--------------------------------------------------------------------------
        | Null marker
        |--------------------------------------------------------------------------
        |
        | Used internally to cache misses and avoid repeated DB hits for the
        | same nonexistent lookup.
        |
        */
        'null_marker' => '__model_channel_null__',
    ],

];