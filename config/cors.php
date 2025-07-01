<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://charming-haupia-7f4b25.netlify.app',
        'https://statuesque-heliotrope-0b87ef.netlify.app',
        'https://ephemeral-rugelach-eec8d2.netlify.app',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
