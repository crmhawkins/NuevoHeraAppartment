<?php

return [
    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'base_uri' => env('OPENAI_BASE_URI', 'https://api.openai.com/v1'),
    'timeout' => env('OPENAI_TIMEOUT', 30),
    'http_client' => null,
];
