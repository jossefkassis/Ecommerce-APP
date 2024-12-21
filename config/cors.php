<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Apply CORS to API routes and Sanctum
    'allowed_methods' => ['*'], // Allow all HTTP methods
    'allowed_origins' => ['*'], // Allow all origins (replace with specific domains in production)
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'], // Allow all headers
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false, // Set to true if you're using cookies or authentication
];
