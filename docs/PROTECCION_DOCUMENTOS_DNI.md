# Protección de documentos DNI subidos por clientes

## Problema que se resolvió (21/04/2026)

Fotos de DNI de clientes se habían estado perdiendo en redeploys del
contenedor Coolify. Causa raíz identificada:

1. El `entrypoint.sh` de Coolify ejecuta `rm -rf * .[!.]*` cuando el
   contenedor se recrea desde cero (sin `.git`). Después hace `git clone`.
2. Como los archivos subidos por clientes **no están en git** (correcto:
   no deben estarlo), el reset los elimina.
3. Además, antes las fotos se guardaban en `public/imagesCliente/`
   → accesible desde internet SIN auth. Problema de RGPD.

## Lo que se ha hecho

1. **Migración de seguridad**: fotos movidas de `public/imagesCliente/`
   (público) a `storage/app/photos/dni/` (privado, servido solo por
   endpoint autenticado).
2. **Controladores actualizados** (`DNIController`, `DNIScannerController`,
   `HuespedesController`) para subir siempre a `storage/app/photos/dni/`.
3. **Endpoint de visualización**: `/admin/reservas-revision-manual/foto/{id}`
   protegido por `auth + role:ADMIN`.
4. **`.gitignore` blindado**: `public/imagesCliente/`, `storage/app/photos/`
   y `storage/app/backups/` ya no se trackean nunca.
5. **Backup horario automático**: comando `backup:fotos-dni` sincroniza
   cada hora las fotos a `storage/backups-externo/photos-dni/`.

## Lo que FALTA por hacer manualmente en Coolify UI

**CRÍTICO**: añadir un volumen Docker dedicado para la carpeta de
uploads para que sobreviva a cualquier recreación del contenedor.

### Pasos en Coolify

1. Entrar al panel Coolify del servidor: `https://coolify.apartamentosalgeciras.com`
   (o la URL que corresponda).
2. Abrir el servicio **Apartamentos Hawkins** (el contenedor
   `laravel-f6irzmls5je67llxtivpv7lx`).
3. Ir a la pestaña **Storages / Volumes**.
4. Añadir un nuevo volumen persistente:

   - **Name**: `hawkins-dni-photos`
   - **Source path** (host): `/data/hawkins-dni-photos`
   - **Destination path** (container): `/var/www/html/storage/app/photos`
   - **Type**: Bind mount (persistente)

5. Pulsar **Save** y **Redeploy** el servicio.

Con esto, aunque Coolify recrea el contenedor completo, la carpeta
`/var/www/html/storage/app/photos/` apunta a `/data/hawkins-dni-photos`
del HOST, que es persistente siempre.

### Verificación tras configurar el volumen

```bash
ssh claude@217.160.39.81
docker inspect laravel-f6irzmls5je67llxtivpv7lx --format '{{json .Mounts}}' | jq
# Debe aparecer el bind /data/hawkins-dni-photos -> /var/www/html/storage/app/photos
```

## Capas de protección actuales (redundancia)

Estado al 21/04/2026:

| Capa | Protege contra | Estado |
|---|---|---|
| 1. `.gitignore` de uploads | Deploy que sobrescribe con git | ✅ ACTIVA |
| 2. Volumen Docker del proyecto | Reinicio del contenedor | ✅ ACTIVA |
| 3. Backup horario interno | Pérdida parcial | ✅ ACTIVA |
| 4. Volumen Docker dedicado a uploads | Recreate del contenedor | ⚠️ PENDIENTE (requiere UI Coolify) |
| 5. Backup externo (fuera del host) | Fallo del servidor físico | ❌ NO IMPLEMENTADO |

## Recomendación adicional: backup externo

Para máxima resiliencia, configurar rsync/rclone diario a un bucket S3
(o Backblaze B2, que es barato) desde el host Coolify. Comando ejemplo:

```bash
# En el host Coolify, añadir al crontab del usuario claude:
0 3 * * * rclone sync /data/hawkins-dni-photos b2:hawkins-dni-backup/$(date +\%Y\%m\%d)/ \
  --exclude ".*" --log-file=/var/log/rclone-dni.log 2>&1
```

Este backup externo protegería contra:
- Caída física del servidor Coolify.
- Borrado accidental masivo.
- Ataque ransomware al host.

Coste estimado Backblaze B2: ~0,005 USD/GB/mes → para 1 GB de fotos DNI,
~0,06 USD/año.
