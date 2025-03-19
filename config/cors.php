<?php

return [

    // Define paths where CORS is enabled
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Allow all HTTP methods (GET, POST, etc.)
    'allowed_methods' => ['*'],

    // Restrict the allowed origins (e.g., your frontend URL)
    'allowed_origins' => ['*'], // Update with your frontend URLs

    // Allow any patterns for origins if needed (optional)
    'allowed_origins_patterns' => [],

    // Allow all headers to be sent in requests
    'allowed_headers' => ['*'],

    // Expose specific headers in responses (optional)
    'exposed_headers' => [],

    // Set max age for preflight requests (optional)
    'max_age' => 0,

    // Allow cookies and credentials to be included in requests
    'supports_credentials' => true, // Enable if cookies or authorization headers are needed
];
