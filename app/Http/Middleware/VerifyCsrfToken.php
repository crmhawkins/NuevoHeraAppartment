<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/channex/*',
        '/webhook-handler',
        '/webhooks/stripe',
        '/whatsapp',
        '/whatsapp-envio',
        'api/webhooks/*',
        'api/checkin-completado',
        'api/whatsapp-tools/*',
        // Endpoint publico de subida de facturas desde movil. La seguridad
        // depende del token en la URL (validado en el controller), no del
        // CSRF cookie, que no tiene sentido en un flujo sin sesion.
        'facturas/subir/*',
    ];
}
