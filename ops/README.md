# Scripts de operaciones del CRM

Scripts que viven en el VPS Linux donde corre el CRM (no son código PHP — son
bash + cron). Se guardan aquí en el repo para tener versionado e historia.

**Ubicación en producción**: `217.160.39.79` (VPS interno, donde vive el
contenedor `laravel-f6irzmls5je67llxtivpv7lx`).

## coolify-laravel-guardian.sh

Protege los archivos `entrypoint.sh` y `supervisord.conf` del servicio
Coolify del CRM contra regeneraciones espontáneas que provocan bucle de
reinicio del contenedor.

**Contexto**: cuando Coolify regenera `entrypoint.sh` (tras updates o
redeploys), incluye un bloque `sed -i` para parchear `supervisord.conf`
que falla con "Resource busy" porque ese archivo es un bind-mount. El
container queda en bucle recompilando extensiones PHP cada 3-5 min
indefinidamente.

El guardian:

1. Detecta si `entrypoint.sh` contiene el bloque peligroso (`sed -i
   "/^\[program:`). Si sí, lo restaura desde `entrypoint.sh.bak`.
2. Se asegura de que `supervisord.conf` tiene `user=www-data` en los
   bloques `[program:scheduler]` y `[program:queue-worker]` (modificando
   el archivo in-place preservando el inode del bind-mount).
3. Si tocó algo, reinicia el contenedor laravel.
4. Idempotente: no hace nada si todo está bien.

**Instalado en**:
- `/usr/local/bin/coolify-laravel-guardian.sh`
- `/etc/cron.d/coolify-laravel-guardian` (cada 2 min)
- Log: `/var/log/coolify-laravel-guardian.log`

## crm-uptime-monitor.sh

Monitor externo de disponibilidad del CRM. Si el sitio devuelve 5xx
(o timeout) 3 veces consecutivas, manda WhatsApp al admin como template
UTILITY aprobado (no depende de ventana 24h).

**Instalado en**:
- `/usr/local/bin/crm-uptime-monitor.sh`
- `/etc/cron.d/crm-uptime-monitor` (cada 1 min)
- State: `/var/lib/crm-uptime-monitor/`
- Log: `/var/log/crm-uptime-monitor.log`

**Template usado**: `alerta_doble_reserva` (el único UTILITY aprobado por
ahora, 4 variables de texto libre; el header fijo "Doble reserva
detectada" es impreciso pero el body muestra la alerta real). Cuando
Meta apruebe `alerta_sistema_hawkins` el header dirá "Alerta Sistema
Hawkins" — no hace falta tocar el script, el código del CRM lo usará
automáticamente.

**Destinatario**: número `34605621704` (Ivan) — hardcoded. Para cambiar
el destinatario o añadir más, editar la variable `ADMIN_PHONE` en el
script y reinstalar.

## Instalación

Ambos scripts se instalan copiándolos al VPS:

```bash
scp ops/coolify-laravel-guardian.sh ops/crm-uptime-monitor.sh claude@217.160.39.79:/tmp/
ssh claude@217.160.39.79 'sudo install -m 0755 /tmp/coolify-laravel-guardian.sh /usr/local/bin/ && sudo install -m 0755 /tmp/crm-uptime-monitor.sh /usr/local/bin/'
```

Cron entries:

```bash
# /etc/cron.d/coolify-laravel-guardian
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
*/2 * * * * root /usr/local/bin/coolify-laravel-guardian.sh

# /etc/cron.d/crm-uptime-monitor
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
MAILTO=""
* * * * * root /usr/local/bin/crm-uptime-monitor.sh >/dev/null 2>&1
```

## Logs

```bash
# Ver actividad reciente del guardian
ssh claude@217.160.39.79 'sudo tail -f /var/log/coolify-laravel-guardian.log'

# Ver actividad del monitor
ssh claude@217.160.39.79 'sudo tail -f /var/log/crm-uptime-monitor.log'
```
