<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cookie Path
    |--------------------------------------------------------------------------
    |
    | This value determines the default path for cookies. The default path
    | is "/" which means the cookie will be available for the entire
    | domain.
    |
    */

    'path' => env('COOKIE_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Default Cookie Domain
    |--------------------------------------------------------------------------
    |
    | This value determines the default domain for cookies. The default domain
    | is null which means the cookie will be available for the current
    | domain only.
    |
    */

    'domain' => env('COOKIE_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Default Cookie Secure
    |--------------------------------------------------------------------------
    |
    | This value determines the default secure setting for cookies. The default
    | is false which means the cookie will only be sent over HTTPS.
    |
    */

    'secure' => env('COOKIE_SECURE', false),

    /*
    |--------------------------------------------------------------------------
    | Default Cookie HTTP Only
    |--------------------------------------------------------------------------
    |
    | This value determines the default http only setting for cookies. The default
    | is true which means the cookie will only be accessible through the HTTP
    | protocol and not through JavaScript.
    |
    */

    'http_only' => env('COOKIE_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Default Cookie Same Site
    |--------------------------------------------------------------------------
    |
    | This value determines the default same site setting for cookies. The default
    | is "lax" which means the cookie will be sent with same-site requests and
    | cross-site top-level navigation requests.
    |
    */

    'same_site' => env('COOKIE_SAME_SITE', 'lax'),

];
