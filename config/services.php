<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'channex' => [
        'webhook_url' => env('CHANNEX_WEBHOOK_URL', 'https://tu-dominio.com/webhook-handler'),
        'api_url' => env('CHANNEX_API_URL', 'https://staging.channex.io/api/v1'),
        'api_token' => env('CHANNEX_API_TOKEN'),
        'webhook_secret' => env('CHANNEX_WEBHOOK_SECRET'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 500),
    ],

    'hawkins_ai' => [
        'url' => env('HAWKINS_AI_URL', 'https://aiapi.hawkins.es/chat/chat'),
        'api_key' => env('HAWKINS_AI_API_KEY'),
        'model' => env('HAWKINS_AI_MODEL', 'gpt-oss:120b-cloud'),
    ],

    'hawkins_whatsapp_ai' => [
        'base_url' => env('HAWKINS_WHATSAPP_AI_URL', 'https://aiapi.hawkins.es/chat/chat'),
        'api_key' => env('HAWKINS_WHATSAPP_AI_API_KEY'),
        'model' => env('HAWKINS_WHATSAPP_AI', 'gpt-oss:120b-cloud'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'ai_translation' => [
        'url' => env('AI_TRANSLATION_URL', 'https://aiapi.hawkins.es/chat/chat'),
        'api_key' => env('AI_TRANSLATION_API_KEY'),
        'model' => env('AI_TRANSLATION_MODEL', 'gpt-oss:120b-cloud'),
    ],

    /*
     * URL del endpoint externo al que se envían datos de reserva (fecha entrada, salida, código).
     * Si está vacío, el botón "Enviar a plataforma" no realizará la petición.
     */
    'plataforma_reservas_url' => env('PLATAFORMA_RESERVAS_URL', ''),

    'checkin' => [
        'url' => env('REGISTRO_VISITANTES_URL', 'https://registro.tudominio.com'),
    ],

    'tuya_app' => [
        'url' => env('TUYA_APP_URL', 'http://localhost:8002'),
        'api_key' => env('TUYA_APP_API_KEY'),
    ],

    'whatsapp' => [
        'token' => env('TOKEN_WHATSAPP'),
        'phone_id' => env('WHATSAPP_PHONE_ID', '102360642838173'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v16.0'),
        'base_url' => 'https://graph.facebook.com',
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'https://192.168.1.45/chat'),
        'api_key' => env('OLLAMA_API_KEY'),
        'model' => env('OLLAMA_MODEL', 'qwen2.5vl:latest'),
    ],

    'facturas' => [
        // Token para el endpoint publico POST /facturas/subir/{token}
        // Se comparte el mismo enlace con todos los trabajadores para subir
        // fotos de facturas desde el movil. Si no esta configurado, el
        // endpoint rechazara cualquier peticion con 403.
        'upload_token' => env('FACTURAS_UPLOAD_TOKEN'),

        // Ventana de tolerancia en dias para emparejar la fecha de la factura
        // con la fecha del gasto en el banco. Por defecto +-15 dias.
        'match_date_window_days' => (int) env('FACTURAS_MATCH_DATE_WINDOW_DAYS', 15),

        // Dias que una factura puede permanecer en "espera" antes de pasar
        // a "error" por falta de gasto candidato.
        'espera_max_dias' => (int) env('FACTURAS_ESPERA_MAX_DIAS', 30),
    ],

    'bankinter' => [
        // Token para el endpoint POST /api/bankinter/scraper/import
        // Lo envia el PC externo (Windows) que ejecuta el scraper en la cabecera
        // X-Scraper-Token. NO tiene valor por defecto: si falta, el endpoint
        // siempre devolvera 401.
        'scraper_api_token' => env('BANKINTER_SCRAPER_API_TOKEN'),

        // Clave simetrica (32 bytes codificados en base64) usada por
        // GET /api/bankinter/scraper/credentials para cifrar el payload con
        // AES-256-GCM. El PC externo debe tener la misma clave para descifrar.
        'encryption_key' => env('BANKINTER_ENCRYPTION_KEY'),

        // Fecha minima de importacion (YYYY-MM-DD). Movimientos anteriores se ignoran.
        // Util para no pisar la tesoreria que ya esta cuadrada manualmente.
        // Dejar vacio para importar todo.
        'import_desde' => env('BANKINTER_IMPORT_DESDE'),

        // Cuenta unica legacy (retrocompatible si no hay 'cuentas')
        'user' => env('BANKINTER_USER'),
        'password' => env('BANKINTER_PASSWORD'),
        'iban' => env('BANKINTER_IBAN'),

        // Multi-cuenta: cada alias tiene perfil Chrome independiente.
        // 'bank_id' es el ID en la tabla bank_accounts del CRM.
        // Para anadir mas bancos en el futuro, se creara una estructura similar.
        'cuentas' => [
            'hawkins' => [
                'user' => env('BANKINTER_USER_HAWKINS', ''),
                'password' => env('BANKINTER_PASSWORD_HAWKINS', ''),
                'iban' => env('BANKINTER_IBAN_HAWKINS', ''),
                'bank_id' => (int) env('BANKINTER_BANK_ID_HAWKINS', 1),
            ],
            'helen' => [
                'user' => env('BANKINTER_USER_HELEN', ''),
                'password' => env('BANKINTER_PASSWORD_HELEN', ''),
                'iban' => env('BANKINTER_IBAN_HELEN', ''),
                'bank_id' => (int) env('BANKINTER_BANK_ID_HELEN', 2),
            ],
            // Anade mas cuentas aqui:
            // 'otra_empresa' => [
            //     'user' => env('BANKINTER_USER_OTRA', ''),
            //     'password' => env('BANKINTER_PASSWORD_OTRA', ''),
            //     'iban' => env('BANKINTER_IBAN_OTRA', ''),
            //     'bank_id' => (int) env('BANKINTER_BANK_ID_OTRA', 3),
            // ],
        ],
    ],

    'whatsapp_tools' => [
        'api_key' => env('WHATSAPP_TOOLS_API_KEY'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

];
