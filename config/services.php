<?php

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Mobile money collection provider used by orders/iotec_collections
    'iotec' => [
        'client_id' => env('IOTEC_CLIENT_ID'),
        'client_secret' => env('IOTEC_CLIENT_SECRET'),
        'base_url' => env('IOTEC_BASE_URL'),
    ],

    // Uganda URA e-invoicing, see efris_config / efris_devices
    'efris' => [
        'environment' => env('EFRIS_ENVIRONMENT', 'sandbox'),
    ],
];
