# Cambios aplicados a Tuyalaravel — 28/04/2026

> Tuyalaravel es un proyecto separado en
> `https://github.com/crmhawkins/tuya-ttlocl-laravel` (rama `main`).
> Los cambios de hoy YA están pusheados al repo (commit `ea5198b`),
> así que sobreviven a un redeploy de Coolify. Esta nota queda como
> registro histórico del incidente.
>
> **Backups locales en el contenedor**: `.bak.20260428` por si hubiera
> que restaurar el estado anterior.

## Endpoints añadidos

### 1. `GET /api/pins/by-reference/{reference}`

Busca un PIN por su `external_reference` (en nuestro caso `reserva_{id}`).
Razón: el CRM guarda el `provider_code_id` (ID Tuya) en
`reservas.ttlock_pin_id`, pero el `GET /api/pins/{id}` original busca por
ID interno autoincrement (1-N). Sin un endpoint estable de búsqueda por
referencia, todos los healthcheck devolvían 404 falsos.

Responde mismo formato que `GET /api/pins/{id}`.

### 2. `GET /api/pins/by-provider-code/{providerCodeId}`

Busca un PIN por `provider_code_id` (ID Tuya). Útil cuando solo conocemos
ese ID y no la `external_reference`.

### 3. `GET /api/locks/{lockId}/pins-count`

Cuenta los PINs activos en un lock. Usado por
`CerraduraSlotManager::asegurarSlotLibre` para saber cuántos slots
físicos están realmente ocupados (incluyendo PINs permanentes de
limpiadora/seguridad y zombies históricos), antes de programar uno nuevo.

Respuesta:
```json
{
  "success": true,
  "data": {
    "lock_id": 1,
    "active_now": 5,
    "registered": 20,
    "timestamp": "2026-04-28T22:52:43+02:00"
  }
}
```

- `active_now`: PINs con `status=registered` y `now` dentro de la ventana
  `effective_time` → `invalid_time`. Esto representa lo que la cerradura
  física tiene ACTIVO en este momento.
- `registered`: PINs con `status=registered` o `pending`, total. Ocupan
  slot aunque aún no estén dentro de ventana.

## Archivos modificados (en el contenedor `tuyalaravel-app`)

- `/var/www/routes/api.php` — 3 rutas nuevas. Backup: `api.php.bak.20260428`.
- `/var/www/app/Http/Controllers/Api/PinController.php` — métodos
  `showByReference`, `showByProviderCode`, `countByLock`.
  Backup: `PinController.php.bak.20260428`.

## Cómo restaurar (si rompe algo)

```bash
ssh -i ~/.ssh/hawcert_server claude@217.160.39.79
docker exec tuyalaravel-app cp /var/www/routes/api.php.bak.20260428 /var/www/routes/api.php
docker exec tuyalaravel-app cp /var/www/app/Http/Controllers/Api/PinController.php.bak.20260428 /var/www/app/Http/Controllers/Api/PinController.php
docker exec tuyalaravel-app php artisan route:clear
```

## Repo de Tuyalaravel

`https://github.com/crmhawkins/tuya-ttlocl-laravel` (rama `main`).

OJO — el repo `https://github.com/ivanhawkins/Tuyalaravel.git` es el
proyecto VIEJO antes del rebrand a "tuya-ttlock". NO trabajar ahí.

Los cambios de hoy están en commit `ea5198b` de `main`.

## Pruebas verificadas el 28/04/2026

- `GET /api/pins/by-reference/reserva_6418` → `{"is_active":true, "id":26, ...}` ✓
- `GET /api/pins/by-provider-code/743283614` → mismo objeto ✓
- `GET /api/pins/by-reference/reserva_99999` → HTTP 404 `{"error":"PIN not found for reference"}` ✓
- `GET /api/locks/1/pins-count` → `{"active_now":5, "registered":20}` ✓

Sin lint errors. Sin afectar a endpoints existentes.
