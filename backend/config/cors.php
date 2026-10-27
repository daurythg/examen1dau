<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [
        '#^https://[a-z0-9-]+-5173\.app\.github\.dev$#',
    ],

    'allowed_headers' => ['Authorization', 'Content-Type', 'Accept'],

    'exposed_headers' => ['Location'],

    'max_age' => 0,

    // Se usa token Bearer, no cookies
    'supports_credentials' => false,

];
