<?php

// return [
//     'paths' => ['api/*', 'sanctum/csrf-cookie', 'resources/js/*'],
//     'allowed_methods' => ['*'],
//     'allowed_origins' => ['*'],
//     'allowed_origins_patterns' => [],
//     'allowed_headers' => ['*'],
//     'exposed_headers' => [],
//     'max_age' => 0,
//     'supports_credentials' => false,
// ];


// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://chat.local'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // Required for cookies
];