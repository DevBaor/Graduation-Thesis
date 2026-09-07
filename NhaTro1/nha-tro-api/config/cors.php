<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'chu-tro/*', 'khach-thue/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:3000',
        'http://localhost:8000',
        'http://localhost:8001',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8000',
        'http://127.0.0.1:8001',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
