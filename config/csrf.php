<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Request Forgery (CSRF) Protection
    |--------------------------------------------------------------------------
    |
    | Laravel automatically generates a CSRF "token" for each active user session
    | managed by the application. This token is used to verify that an authenticated
    | user is the one actually making the requests to the application.
    |
    | When you set up CSRF protection, all HTML forms in your application should
    | include a hidden CSRF token field so that the CSRF protection middleware can
    | validate the request. You may use the @csrf Blade directive to generate the
    | token field.
    |
    | You can change the settings below to configure the CSRF protection.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Name
    |--------------------------------------------------------------------------
    |
    | The name of the token field in the form. You can change this value
    | if you want to use a different name for the token field.
    |
    */

    'token_name' => env('CSRF_TOKEN_NAME', '_token'),

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Length
    |--------------------------------------------------------------------------
    |
    | The length of the CSRF token. The default is 32 characters, but you can
    | change this value if you need a different length.
    |
    */

    'token_length' => env('CSRF_TOKEN_LENGTH', 32),

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Expiration
    |--------------------------------------------------------------------------
    |
    | The number of minutes that a CSRF token is valid. After this time,
    | the token will expire and a new one will need to be generated.
    |
    */

    'token_expiration' => env('CSRF_TOKEN_EXPIRATION', 120),

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Regeneration
    |--------------------------------------------------------------------------
    |
    | Whether to regenerate the CSRF token on every request. This can help
    | prevent CSRF attacks but may cause issues with some JavaScript frameworks.
    |
    */

    'regenerate_token' => env('CSRF_REGENERATE_TOKEN', false),

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Storage
    |--------------------------------------------------------------------------
    |
    | The storage driver to use for storing CSRF tokens. The default is 'session',
    | but you can also use 'cache' or 'database'.
    |
    */

    'storage' => env('CSRF_STORAGE', 'session'),

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Cache Store
    |--------------------------------------------------------------------------
    |
    | The cache store to use when storing CSRF tokens in cache. This is only
    | used when the storage driver is set to 'cache'.
    |
    */

    'cache_store' => env('CSRF_CACHE_STORE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Database Table
    |--------------------------------------------------------------------------
    |
    | The database table to use when storing CSRF tokens in the database. This
    | is only used when the storage driver is set to 'database'.
    |
    */

    'database_table' => env('CSRF_DATABASE_TABLE', 'csrf_tokens'),

];
