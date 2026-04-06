@component('mail::message')
# Restablecer Contraseña - Hawkins Suite

¡Hola! Has solicitado restablecer tu contraseña.

## ¿Qué pasó?

Recibimos una solicitud para restablecer la contraseña de tu cuenta en Hawkins Suite. Si fuiste tú quien la solicitó, haz clic en el botón de abajo para continuar.

## Acción requerida

@component('mail::button', ['url' => $url, 'color' => 'primary'])
Restablecer Contraseña
@endcomponent

## Información importante

- **Este enlace expirará en {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutos**
- **Si no solicitaste este cambio, ignora este correo**
- **Tu contraseña actual permanecerá sin cambios hasta que completes el proceso**

## ¿Necesitas ayuda?

Si tienes problemas para restablecer tu contraseña, contacta con nuestro equipo de soporte.

## Seguridad

Por tu seguridad, este enlace solo puede ser usado una vez. Si necesitas restablecer tu contraseña nuevamente, solicita un nuevo enlace.

---

**Hawkins Suite** - Gestión integral de apartamentos turísticos

<small>Este es un correo automático, por favor no respondas a este mensaje.</small>
@endcomponent
