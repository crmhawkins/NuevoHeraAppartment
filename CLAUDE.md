# NuevoHeraAppartment — Memoria del proyecto

Archivo de memoria local para retomar contexto en conversaciones futuras.
Última actualización: **2026-05-05**.

---

## VERSIONES ESTABLES DE REFERENCIA

### Repo `crmhawkins/NuevoHeraAppartment` (CRM)

| Tag | Commit | Lo que añade |
|---|---|---|
| `stable-2026-04-29` | `e478878` | Base de la jornada catastrófica del 29/04 (cache root:root, etc.) |
| `stable-2026-05-01` | `5f55deb` | PIN portal 6 dígitos `00XXXX`, fallback IA cloud→local en flujos críticos, MIR consolidado 11h, cron `checkin:verificar-hoy` deshabilitado |
| `stable-2026-05-04` | `0b15ec0` | Cámara DNI con overlay SVG, 3 hotfixes claves canónicos, no bloquear claves por falta de DNI, admin marca ticks limpieza sin turno propio |

### Repo `crmhawkins/tuya-ttlocl-laravel` (servicio cerraduras)

| Tag | Commit | Lo que añade |
|---|---|---|
| `stable-2026-05-02` | `6c0e732` | Soporte categoría `mk` con 6 dígitos, validación HTTP `digits:6` cuando `metadata.category=mk` |
| `stable-2026-05-05` | `6f09745` | **Hardening mk**: verificación post-creación + 3 reintentos + activa fallback automático si Tuya cloud da `success` falso |

**Si algo se rompe en futuras sesiones y no se identifica claramente la
causa, volver aquí**:
```bash
git checkout stable-2026-05-04   # CRM
git checkout stable-2026-05-05   # tuyalaravel
```

Nunca borrar estos tags sin acuerdo con el cliente.

---

## 0. REGLAS INVIOLABLES DEL SISTEMA DE CERRADURAS

**Establecidas por el cliente el 29/04/2026 tras incidente de saturación
de slots y envío masivo de WhatsApp de emergencia. NO TOCAR.**

### 0.1 Capacidad máxima por cerradura: 9 PINs

Cada cerradura física Tuya/TTLock tiene **9 slots** y se distribuyen así:

- **7 slots** para huéspedes que ENTRAN HOY (PINs dinámicos por reserva)
- **1 slot** para PIN de **seguridad/emergencia** (fijo, permanente)
- **1 slot** para PIN de **limpiadoras** (fijo, permanente)

Total: **9. Nunca más.** Si el sistema intenta meter un 10º PIN, falla
con `"The number of passwords has reached the limit"`. Eso significa
que se está incumpliendo alguna de las reglas siguientes.

### 0.2 PROHIBIDO programar PINs futuros

**NO se pre-programan PINs con días/semanas/meses de antelación.**

Si una reserva entra dentro de 3 días, el PIN se queda PENDIENTE en BD
(`codigo_enviado_cerradura = 0`) y NO se manda a la cerradura física
hasta el día de la entrada.

Cualquier código que recorra reservas y dispare `Tuyalaravel POST /api/pins`
con `effective_time > hoy + 1 día` está MAL. Hay que cambiarlo a
"solo programar reservas que entran HOY".

### 0.3 Borrado obligatorio el día de salida a las 11:00

Cuando un huésped se va (`fecha_salida = hoy`, hora `11:00`):

1. El sistema BORRA el PIN de su reserva en la cerradura física
   (`Tuyalaravel DELETE /api/pins/{provider_code_id}`)
2. Libera el slot
3. Sólo entonces puede programar al siguiente

### 0.4 Procedimiento diario obligatorio

Cada día (idealmente cron a las 11:00 + revisión a las 14:00):

1. **BORRAR salientes**: para todas las reservas con `fecha_salida = hoy`,
   borrar su PIN en cerradura. Confirmar slot liberado.
2. **PROGRAMAR entrantes**: para todas las reservas con `fecha_entrada = hoy`,
   programar su PIN en cerradura ahora.
3. **Verificar slots**: cada cerradura debe quedar con ≤ 9 PINs registrados.
   Si supera, hay zombies → purgarlos.

Este orden es crítico: primero borrar, después programar. Si no, los
slots de los salientes bloquean a los entrantes.

### 0.5 Cómo se debió implementar (vs. cómo está hoy)

**Hoy** (estado defectuoso):
- `cerraduras:programar-proximas` programa PINs con ventana de hasta 7 días
  (Tuya) o 150 días (TTLock) por adelantado.
- `BorrarPinAlVencer` se programa con `delay()` en queue al crear el PIN —
  si la queue se atasca o se reinicia el contenedor mal, el PIN no se
  borra y queda zombie.
- Resultado: la cerradura se llena de PINs futuros + zombies y bloquea
  los del día (incidente Hawkins Suites 29/04/2026).

**Como debe ser**:
- Cron diario `cerraduras:rotacion-diaria`:
  1. Borra los PINs de reservas con `fecha_salida <= hoy` y `codigo_enviado_cerradura = 1`.
  2. Programa los PINs de reservas con `fecha_entrada = hoy` y `codigo_enviado_cerradura = 0`.
  3. Verifica conteo final de slots en cada lock; si > 9 alerta admin.
- `cerraduras:programar-proximas` desactivado o limitado a `fecha_entrada <= hoy + 1`
  como red de seguridad, no como flujo principal.
- `BorrarPinAlVencer` queda como fallback secundario (no como mecanismo
  principal de borrado).

### 0.6 Si una cerradura está saturada — protocolo

NO ejecutar masivamente `AccessCodeService::generarYProgramar` para
varias reservas de golpe. Cada llamada que falla incrementa
`fallos_consecutivos_tuya`; al llegar a 3 se activa el modo fallback y
**se mandan automáticamente 17+ WhatsApp de "cambio de clave de
emergencia" a TODOS los huéspedes activos del edificio**. Es
irreversible (los mensajes ya están enviados).

Si la cerradura está saturada:
1. Primero `php artisan cerraduras:purgar-zombies` para liberar slots.
2. Comprobar `GET /api/locks/{id}/pins-count` que `registered <= 9`.
3. Solo entonces reintentar programar.
4. Si el fallback se activó por accidente, hay que avisar al cliente
   con un mensaje aclarativo manualmente.

---

## 1. Qué es este proyecto

CRM Laravel 10 (PHP 8.3) para **Hera Apartments** (alquiler turístico en
Algeciras, Cádiz). Gestiona:

- Reservas, apartamentos, edificios, clientes, huéspedes
- Facturación (presupuestos + facturas + rectificativas)
- Limpieza (tareas, turnos, fotos, análisis IA)
- Incidencias (avería/mantenimiento)
- Integración MIR (Ministerio del Interior — notificación obligatoria
  de hospedajes)
- Scraping bancario Bankinter + conciliación con facturas
- WhatsApp bot (cliente + staff) via Meta Business API
- Subida móvil de facturas con OCR/IA

---

## 2. Infraestructura

### Servidor interno (Coolify)
- **IP**: 217.160.39.79
- **SSH**: `ssh -i ~/.ssh/hawcert_server claude@217.160.39.79`
  (user `claude`, sudo sin password, grupo docker)
- **RAM**: 32 GB | **Disco**: 434 GB

### Contenedor principal
- **Nombre**: `laravel-f6irzmls5je67llxtivpv7lx`
- **URL pública**: https://crm.apartamentosalgeciras.com
- **DB**: MariaDB, schema `crm_apartamentos`
- **Ruta raíz dentro del contenedor**: `/var/www/html`
- **.env**: `/var/www/html/.env` (incluye `FACTURAS_UPLOAD_TOKEN`)

### GitHub
- **Org**: crmhawkins
- **Repo**: https://github.com/crmhawkins/NuevoHeraAppartment
- **Branch principal**: `main` (todos los commits van aquí, push directo)

---

## 3. Estructura local de trabajo

### IMPORTANTE: dónde editar
- **Path principal**: `D:\proyectos\programasivan\NuevoHeraAppartment`
  TODO se edita aquí. Git status y commits salen de este path.
- Hay un worktree `.claude/worktrees/gallant-chatterjee` que en el
  pasado causó confusión. **Ignóralo** — está desfasado respecto a main.

### Flujo de deploy típico
1. Editar archivos en el path principal
2. `scp` el archivo a `/tmp/` en el servidor
3. `docker cp /tmp/archivo CONTAINER:/var/www/html/ruta/archivo`
4. `docker exec CONTAINER php -l /var/www/html/ruta/archivo` (sintaxis)
5. Si es blade: `php artisan view:clear && php artisan view:cache`
6. Si es ruta/config: `php artisan route:clear && php artisan config:clear`
7. Si es migración: `php artisan migrate --path=database/migrations/YYYY_...`
8. `git add` + `git commit` + `git push origin main`

### Template de commit (estilo que sigo en este repo)
```
Feat|Fix|Refactor: breve descripcion

Explicacion tecnica de 3-5 lineas con CAUSA y CONSECUENCIA.

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>
```

---

## 4. Servicios clave

| Service | Propósito |
|---|---|
| `AIGatewayService` | Chat completions con fallback OpenAI→Hawkins AI (qwen3). Circuit breaker. Siempre usar este en vez de llamar a OpenAI directo. |
| `MIRService` | SOAP al Ministerio del Interior para notificar hospedajes. Valida CP antes de enviar. |
| `SpanishPostalCodeValidator` | Validación de códigos postales españoles (rangos provinciales + blacklist). |
| `BankinterScraperService` | Importa movimientos bancarios. Resuelve bank_id a Bankinter y categoriza via IA. |
| `TecnicoNotificationService` | Envía incidencias a técnicos por WhatsApp (template). Sanitiza `\n`/`\t`. |
| `WhatsappNotificationService` | WhatsApp genérico al equipo (`sendToConfiguredRecipients`). |
| `AlertaEquipoService` | Alertas críticas al equipo de gestión. |

---

## 5. Modelos importantes (tablas y FKs)

### Facturación
- `Invoices` (tabla `invoices`) — campo `reference` formato `YYYY/MM/NNNNNN`,
  rectificativas `RYYYY/MM/NNNNNN` (misma numeración que la original).
  FK `reference_autoincrement_id` → `invoices_reference.id`.
- `InvoicesReferenceAutoincrement` (tabla **`invoices_reference`**, NO
  `invoices_reference_autoincrements`). Lleva contador por year+month.
- `InvoiceDownloadToken` (tabla `invoice_download_tokens`) — tokens de
  descarga pública con TTL 30 días.
- `Presupuesto` → `PresupuestoConcepto` (tabla `presupuesto_conceptos`):
  `tipo` = `alojamiento`|`servicio`, `iva` guarda el % (10 o 21),
  `unidades` solo aplica en servicio.

### Limpieza
- `ApartamentoLimpieza` (`status_id` → `apartamento_estado.id`)
- `ApartamentoLimpiezaEstado` (tabla `apartamento_estado`):
  - 1 = Sucio
  - 2 = En Limpieza
  - 3 = Limpio
  - 4 = **No realizada** (creado 2026-04-17)
- `ApartamentoLimpiezaItem` (`photo_url`) — fotos en formato
  `limpiezas/{id}/xxx.jpg` (storage) o `images/xxx` (public).

### Incidencias
- `Incidencia` — campo `metodo_notificacion` se rellena con 'whatsapp'
  cuando `TecnicoNotificationService` consigue enviar.

### Clientes / bancos
- `Cliente` — campos `facturacion_*` para datos de empresa,
  `tipo_cliente` = `particular`|`empresa`|`autonomo`.
- `Bancos` (tabla `bank_accounts`): id=1 BANKINTER, id=2 CAJA.
- `CategoriaGastos`: 1=NOMINA, 3=COMISION BOOKING, 4=COMISION AIRBNB,
  5=COMISION STRIPE, 6=SEGUROS SOCIALES, 24=AMENITIES, 30=COMISION
  BANCARIA, 31=ASESORIA, 32=ASCENSOR, 33=MODELO 303, 34=MODELO 111,
  45=DEVOLUCION SOCIO, 46=ELECTRICIDAD, 47=AGUA, 48=TELEFONIA,
  51=OTROS, 52=MATERIALES OBRA, 53=PRESTAMOS, 57–68=categorías OBRA.
  Hay flag `contabilizar_misma_empresa` para separar en dashboard.

---

## 6. Integraciones externas

| Servicio | Cómo se usa |
|---|---|
| **OpenAI** | Via `AIGatewayService->chatCompletion()`. Modelo default `gpt-4`. |
| **Hawkins AI** | Fallback cuando OpenAI falla. `aiapi.hawkins.es/chat/chat`. SSL bypass activo (cert caducó). Modelos: `qwen3:latest` (texto), `qwen2.5vl:latest` (visión), `gpt-oss:120b-cloud`. |
| **WhatsApp Business API** | `Setting::whatsappToken()` + `Setting::whatsappUrl()`. Templates: `reparaciones`, `limpieza`, `alerta_doble_reserva`. Los textos de parámetros NO admiten `\n`, `\t`, ni >4 espacios seguidos. |
| **MIR (hospedajes.ses.mir.es)** | SOAP con credenciales `mir_codigo_arrendador`, `mir_password`. Config producción: arrendador `0000060524`, password `vc52t@6U4VXwXsP`. Valida CP contra callejero español. |
| **Bankinter** | Scraping via Puppeteer (node). Cuentas en `config/services.bankinter.cuentas` con alias (`hawkins`, `helen`) y `bank_id`. |
| **Channex** | Gestión de reservas OTA. Ruta API interna en `WebhookController`. |
| **DomPDF** | `Barryvdh\DomPDF\Facade\Pdf::loadView(...)` para PDFs de facturas. |

---

## 7. Trabajos recientes importantes (abril 2026)

### Sesión 2026-04-15/17 — fixes CRM masivos

#### Bankinter scraper (`app/Services/BankinterScraperService.php`)
- Banco se redirige siempre a BANKINTER (antes aparecía CAJA)
- Categoría decidida por IA (antes era siempre NOMINA)
- Cache intra-proceso por concepto

#### Presupuestos
- Migración `2026_04_15_200000_add_tipo_unidades_to_presupuesto_conceptos.php`
- Tipo `servicio` con selector IVA 10/21 en form + edit
- `facturar()` ahora calcula base+iva por concepto usando el % guardado
- Nuevo endpoint `POST /presupuestos/cliente-rapido` para crear cliente
  desde el modal (antes no funcionaba porque faltaban campos obligatorios)

#### Facturas
- **Race condition corregido** en `Kernel.php::generateBudgetReference()`:
  usa ahora `DB::transaction` + `lockForUpdate` y cruza contra
  `invoices.reference` para no colisionar. El salto histórico 0042→0179
  (ocurrido antes del fix) NO se rellenó.
- **PDF IVA dinámico**: `previewPDF.blade.php:219` calcula el % real desde
  `iva/base`. Antes era hardcoded 21%/10% según `reserva_id`.
- **Abril 2026 renumerado** el 2026-04-17: 86 facturas consecutivas
  `000001`–`000086` **ordenadas por fecha de checkout**. Backup en el
  contenedor: `/tmp/invoices_backup_20260417_113219/`.
- **Rectificativas 4818 y 4819** renombradas con R + mismo número que la
  original: `R2026/01/000062` y `R2026/01/000178`.
- **Envío al cliente**: migración `2026_04_17_120000_create_invoice_download_tokens_table.php`,
  botón en listado, ruta pública `/facturas/descargar/{token}`, envío
  simultáneo WhatsApp + email.
- **Listado de facturas simplificado**: solo 4 botones (Ver, Descargar,
  Enviar al cliente [si no se ha enviado], Rectificar). Eliminados
  "Recalcular IVA" y "Cambiar fecha + recalcular".

#### Limpieza
- Botón **"No realizada"** en detalle (`admin.limpiezas.marcarNoRealizada`)
- **Cap 8h/jornada** en `GenerarTurnosTrabajo` (antes usaba solo
  `horas_contratadas_dia` sin techo)
- **Fotos rotas**: blade `admin/limpiezas/show.blade.php` detecta si el
  path empieza por `images/` (public) o por otra cosa (storage) y
  construye la URL correcta.

#### MIR (Ministerio del Interior)
- `SpanishPostalCodeValidator`: valida CP español por prefijo provincial
  + rango + blacklist empírica (incluye `11070` rechazado real)
- `MIRService::validarCodigosPostales(Reserva $r)` bloquea envío si CP
  inválido
- `MIRService::enviarSiLista` emite alerta WhatsApp una vez por reserva
  (dedup 24h en Cache) cuando bloquea por CP
- Reserva 6269 corregida manualmente (CP 11070 → 11204 para Avda. Bélgica
  Algeciras, cliente 5852 + huésped 2560)

#### Clientes (`ClientesController`)
- `update()` ya no blanquea campos (`fecha_nacimiento`, `nacionalidad`,
  `idiomas`) cuando el form los envía vacíos. Filtra con un foreach.
- Validaciones `required` relajadas a `nullable` en update.
- `store()` ya NO rechaza emails duplicados (huéspedes recurrentes).
- `edit.blade.php`: eliminado `<input name="facturacion_nif_cif">`
  duplicado que colisionaba.

#### Incidencias (`GestionIncidenciasController::store`)
- Ahora dispara `TecnicoNotificationService` + `AlertaEquipoService` al
  crear una incidencia desde el CRM (antes solo alerta interna).
- `TecnicoNotificationService::enviarMensajeTemplate` sanitiza params
  (WhatsApp rechazaba `\n` con error 100).

### Sesión anterior (pre-compact): fixes Bankinter DUP-SAFE, migración
OpenAI→AIGatewayService de múltiples controllers, CategorizeEmails con
parsing resiliente (qwen3 no devuelve formato estricto).

---

## 8. Reglas operacionales (aprendidas)

### Seguridad / contabilidad
- La numeración de facturas es **legal en España**. No rellenar huecos
  sin asesor fiscal. Los saltos históricos se justifican como "error
  técnico" en acta contable.
- Rectificativas: misma numeración que la original + "R" delante.
- Siempre hacer **backup antes** de tocar tabla `invoices` o
  `invoices_reference`.

### WhatsApp
- Para usar templates Meta, los parámetros de texto NO pueden llevar
  `\n`, `\t`, ni >4 espacios seguidos. Sanitizar siempre.
- Para mensajes libres sin template (type=text) no hay esa restricción.
- Teléfonos: normalizar a formato `34XXXXXXXXX` (sin +, sin espacios).

### IA (gateway)
- Default model `gpt-4` (OpenAI) con fallback `qwen3:latest` (Hawkins).
- Cuando OpenAI rechaza por cuota, el circuit breaker abre y manda
  automáticamente a Hawkins durante 10 min.
- Para tareas de clasificación usar `temperature: 0.0` y parser resiliente
  (ver `CategorizeEmails::extraerCategoriaResiliente` como referencia).

### MIR
- `mir_estado` values: `enviado`, `error`, `error_cp` (nuevo 2026-04-17),
  `error_10121` (lote duplicado).
- Emails de rechazo del MIR llegan horas después del envío SOAP OK.
  El scraping de emails está en `CategorizeEmails` + lectura IMAP en
  `InvoicesController::listarEmailsPendientes`.

### Limpieza de fotos
- `PhotoController::store` guarda en `public/images/xxx`.
- `GestionApartamentoController::fotoRapida` guarda en
  `storage/app/public/limpiezas/{id}/xxx` — necesita `php artisan storage:link`.

---

## 9. Pendientes conocidos

### Responsabilidad del usuario
- [ ] Probar botón "Enviar al cliente" en una factura real
- [ ] Verificar que el cron `everyMinute` del Kernel genera facturas
      al checkout (hay `Log::info("Generando factura...")` en
      `storage/logs/laravel.log`)
- [ ] Considerar si quiere marcar `contabilizar_misma_empresa = true`
      en las CategoriaGastos de OBRA (para separarlas en dashboard)
- [ ] Decidir cómo justificar el hueco histórico 0043–0178 con asesor fiscal

### Posibles mejoras (out of scope hoy)
- Migrar `app/Services/ClienteService.php` al gateway (tiene 2 llamadas
  directas a OpenAI en L326, L377)
- Código muerto en `MovimientosController::uploadExcel` (return antes
  del foreach)
- `Http` facade import unused en `PhotoAnalysisController`
- Broken "8 consecutive spaces" rule en plantillas de WhatsApp puede
  volver si se añaden más templates; hay que sanitizar siempre

---

## 10. Snippets útiles para tinker

```php
// Ejecutar tinker desde script
docker cp /tmp/X.php CONTAINER:/tmp/X.php
docker exec CONTAINER sh -c 'cd /var/www/html && php artisan tinker /tmp/X.php'

// Listar reservas sin factura con checkout pasado
use Carbon\Carbon;
$hoy = Carbon::now()->subDay();
$ini = Carbon::now()->subDays(8);
Reserva::whereDate('fecha_salida', '>=', $ini)
    ->whereDate('fecha_salida', '<=', $hoy)
    ->whereNotIn('estado_id', [4])
    ->whereDoesntHave('invoices')
    ->get(['id','cliente_id','fecha_salida','precio','no_facturar']);

// Ver todas las references 2026/04 ordenadas
DB::table('invoices')->where('reference','like','2026/04/%')
    ->orderBy('reference')->pluck('reference');

// Forzar envío MIR de una reserva
$r = Reserva::find(ID);
app(App\Services\MIRService::class)->enviarSiLista($r);
```

---

## 11. Dónde NO tocar sin pensarlo dos veces

- **`Invoices::reference`** masivamente → legal
- **`MIRService::enviarReserva`** → producción real con Gobierno de España,
  cualquier error se traduce en sanción económica
- **`presupuesto_conceptos.iva`** semánticamente cambió (era €, ahora %)
  → cualquier código que lea este campo asumiendo € está roto
- **Numeración `YYYY/MM/NNNNNN`** → inmutable una vez emitida al cliente.
  Abril 2026 se renumeró SOLO porque ninguna se había enviado aún.

---

## 12. Contactos / credenciales

Ver `C:\Users\ivanj\.claude\CLAUDE.md` (memoria global del usuario) para
credenciales SSH y detalles de otros contenedores.

---

## 13. INFRAESTRUCTURA — referencia rápida

### Servidores (SSH)

| Alias | IP | Comando |
|---|---|---|
| Externo (Coolify, ~220 contenedores) | `217.160.39.81` | `ssh -i ~/.ssh/hawcert_server claude@217.160.39.81` |
| **Interno (este proyecto)** | `217.160.39.79` | `ssh -i ~/.ssh/hawcert_server claude@217.160.39.79` |
| IA Pruebas (RTX 4080) | `192.168.1.250` | `ssh servidor-ia-prueba@192.168.1.250` (red Hawkins/VPN) |
| IA Producción (5090) | `192.168.1.45` | `ssh hawkins@192.168.1.45` (red Hawkins/VPN) |

Usuario `claude` tiene sudo sin password, grupo docker.

### Contenedores que toca este proyecto (servidor 217.160.39.79)

| Contenedor | Rol | Path raíz | Bind mount host |
|---|---|---|---|
| `laravel-f6irzmls5je67llxtivpv7lx` | **CRM Hawkins Apartments** (`crm.apartamentosalgeciras.com`) | `/var/www/html` | (Coolify, no bind) |
| `nginx-f6irzmls5je67llxtivpv7lx` | Nginx del CRM | — | — |
| `mariadb-f6irzmls5je67llxtivpv7lx` | BD MariaDB del CRM (`crm_apartamentos`) | — | — |
| **`tuyalaravel-app`** | Servicio HTTP cerraduras (Tuya + TTLock) | `/var/www` | `/home/claude/tuyalaravel/app → /var/www` |
| `tuyalaravel-db` | Postgres 16 BD del servicio cerraduras | — | — |
| `tuyalaravel-redis` | Redis cache | — | — |
| `aiapi` | Hawkins AI wrapper (puerto 11435) | — | — |

**IMPORTANTE — bind mount tuyalaravel**: el código del servicio de cerraduras
está en el host (`/home/claude/tuyalaravel/app/`), no dentro del contenedor.
Para editar: tocar el host directamente, el contenedor lo ve en tiempo real.
Para git: `cd /home/claude/tuyalaravel/app && git ...`

### IPs de red interna (entre contenedores)

- Hawkins AI (cloud→local fallback): `http://10.0.0.1:11435/chat/chat`
- Tuyalaravel desde el CRM: `http://tuyalaravel-app:8000/api/...`
- Channex (externo): `https://app.channex.io/api/v1/...`
- Tuya cloud (externo): `https://openapi.tuyaeu.com/v1.0/...`

---

## 14. CERRADURAS TUYA — endpoints, cifrado, verificación

### Categorías de cerradura (campo `metadata.category` en `locks`)

Tuya tiene varias categorías y CADA UNA usa endpoints distintos:

| Categoría | Tipo físico | API correcta | Long. PIN |
|---|---|---|---|
| **`ms`** | Smart Lock tradicional (BLE/Zigbee + módulo WiFi gateway) | `/v1.0/devices/{id}/door-lock/temp-password` (POST con cifrado AES) | 7 dígitos |
| **`mk`** | **WiFi Access Control** (puerta del portal santísimo) | `unlock_method_create` via `/v1.0/devices/{id}/commands` | **6 dígitos** |
| `ttlock` | TTLock (no Tuya) | API distinta TTLock | 4-8 dígitos |

`Lock::getPinMinLengthAttribute()` y `getPinMaxLengthAttribute()` en
`tuyalaravel/app/Models/Lock.php` calculan la longitud según provider +
metadata.category.

### Cerraduras existentes (BD postgres `tuyalaravel`)

| lock_id | Nombre | device_id Tuya | Categoría | PIN físico permanente |
|---|---|---|---|---|
| 1 | Portal Hawkins Suites (interior 1B, NO usar) | `bfb9c80d3fa0b59b6daugi` | (default) | — |
| **2** | **Portal Hawkins Suites (real)** | `bfc3cba82e8969736bxqhr` | **`mk`** | `001981` (Elena, user_id `4iosly`) |

El user permanente `4iosly` lo creó el cliente desde la app Tuya Smart
como red de seguridad. **El sistema NUNCA borra ese PIN.**

### Endpoints de Tuya cloud que SÍ funcionan para `mk`

Toda llamada requiere `client_id`, `access_token`, `t` (timestamp ms),
`sign` (HMAC-SHA256), `sign_method: HMAC-SHA256`.

```
# Token
GET    /v1.0/token?grant_type=1                              → access_token

# Detalles del device
GET    /v1.0/devices/{deviceId}                              → name, online, category
GET    /v1.0/devices/{deviceId}/users                        → users permanentes (no PINs temporales)
GET    /v1.0/iot-03/devices/{deviceId}/status                → DPs raw del device (base64 binario)
GET    /v1.0/iot-03/devices/{deviceId}/specifications        → funciones soportadas
GET    /v1.0/iot-03/devices/{deviceId}/functions             → funciones con tipos

# Crear/borrar PIN — ÚNICA vía que funciona en mk
POST   /v1.0/devices/{deviceId}/commands
  body: {"commands":[{"code":"unlock_method_create","value":"<JSON string>"}]}
  value: {"userid":<int32>,"type":0,"code":"<PIN>","name":"<≤20 chars>","starttime":<ts>,"endtime":<ts>}

POST   /v1.0/devices/{deviceId}/commands
  body: {"commands":[{"code":"unlock_method_delete","value":"{\"userid\":<int>}"}]}

# VERIFICAR que el PIN se publicó al device (clave del hardening 5/5)
GET    /v1.0/devices/{deviceId}/logs?end_time=<ms>&size=50&start_time=<ms>&type=5
       (params ORDENADOS alfabéticamente para el sign)
       → result.logs[] con events de tipo "unlock_method_create" / "unlock_method_delete"
       → verificar que el log contiene el `userid` del PIN recién creado
```

### Endpoints que NO funcionan para `mk` (sí para `ms`)

```
GET    /v1.0/devices/{id}/door-lock/temp-passwords     → devuelve [] aunque haya PINs (es para ms)
POST   /v1.0/devices/{id}/door-lock/temp-password      → 500 system error (no soporta mk)
POST   /v1.0/devices/{id}/door-lock/password-ticket    → funciona pero el ticket no sirve para mk
```

### Cifrado de PIN (solo para cerraduras `ms` — no aplica a `mk`)

Para cerraduras `ms` que sí usan `door-lock/temp-password`:

```
1. POST /v1.0/devices/{id}/door-lock/password-ticket
   → response.result = {ticket_id, ticket_key (HEX 64 chars = 32 bytes), expire_time}

2. ticket_bytes = hex2bin(ticket_key)

3. derived = AES-{secLen*8}-ECB-DECRYPT(ticket_bytes, client_secret)
   - secLen=16 → AES-128-ECB
   - secLen=24 → AES-192-ECB
   - secLen=32 → AES-256-ECB (lo habitual en Tuya)

4. key16 = derived[:16]   # primeros 16 bytes

5. encrypted = AES-128-ECB-ENCRYPT(pin, key16)   # PKCS7 padding (default OpenSSL)

6. password_field = strtoupper(bin2hex(encrypted))
```

Para `mk` NO se cifra el PIN: va en claro dentro del JSON del comando
`unlock_method_create`.

### Hardening mk implementado el 05/05/2026 (`createTempPasswordMK`)

El flujo nuevo verifica que el PIN realmente llegó al hardware:

```
1. enviar unlock_method_create
2. esperar 5s
3. consultar /v1.0/devices/{id}/logs?type=5 con el rango temporal
4. buscar log unlock_method_create cuyo value contenga el userid asignado
5. si SÍ → return id
6. si NO → reintentar con userid distinto, hasta 3 veces
7. tras 3 fallos → throw Exception
   → AccessCodeService captura, suma 1 al contador del edificio
   → al 3er fallo del edificio se activa modo fallback
   → todos los huéspedes reciben código emergencia (`001981`)
```

Esto resuelve el caso silencioso donde Tuya cloud devolvía
`success: true` pero el PIN no se sincronizaba al device físico.

### Sistema de fallback (`CerraduraFallbackService`)

Constantes:
- `UMBRAL_FALLOS = 3` (fallos consecutivos para activar)
- `VENTANA_REENVIO_DIAS = 7` (notifica a reservas con check-in dentro de 7d)

Campos en tabla `edificios`:
- `fallback_tuya_activo` (bool)
- `fallback_tuya_activado_at` (timestamp)
- `fallos_consecutivos_tuya` (int)
- `codigo_emergencia_portal` (string) ← el PIN que se manda en fallback
- (mismas variantes con `_ttlock_`)

Para activar/desactivar manualmente:
```bash
docker exec -u www-data laravel-f6irzmls5je67llxtivpv7lx \
    php artisan cerraduras:desactivar-fallback {edificio_id} {tuya|ttlock}
```

**OJO**: al activarse el fallback, se reenvía automáticamente el código
de emergencia a TODOS los huéspedes con check-in en los próximos 7 días
(template Meta `cambio_clave_emergencia`). Pueden ser 17+ mensajes.

---

## 15. CRONS RELEVANTES (Kernel.php)

```
0   *   * * *  cerraduras:programar-proximas        # cada hora
0,30 *  * * *  cerraduras:healthcheck-pins          # cada 30 min
0   *   * * *  cerraduras:probar-recuperacion       # cada hora
5   11  * * *  cerraduras:rotacion-diaria           # 11:05 todos los días
0   3   * * 1  cerraduras:purgar-zombies            # lunes 3 AM

0   14  * * *  ari:enviar-claves-channex            # claves a huéspedes
0   10,22 * * * mir:enviar-pendientes               # MIR pendientes
*/10 * * * *   mir:reintentar-revalidacion          # cada 10 min
0,30 *  * * *  mir:auto-rescate                     # cada 30 min
0   11  * * *  mir:resumen-pendientes               # ÚNICA alerta MIR del día (consolidada)

0   10  * * *  fichajes:verificar-limpiadoras       # 10:00
30  17  * * *  fichajes:verificar-limpiadoras       # 17:30
6   30  * * *  revenue:scrape-nocturno              # 06:30
```

**Cron `checkin:verificar-hoy` (08:00) DESHABILITADO el 01/05** porque era
un eco del resumen MIR de las 11:00.

---

## 16. CAMPOS CANÓNICOS DE LA RESERVA (post-refactor 26/04)

```
codigo_acceso       — LEGACY, no usar para nuevos flujos
codigo_portal       — PIN del PORTAL del edificio (dinámico de cerradura, o emergencia)
codigo_apartamento  — Clave fija del piso (mecánica, en codigo_acceso o claves)
codigo_enviado_cerradura — bool, indica si se programó en cerradura física
codigo_fallback_enviado  — bool, indica si recibió mensaje de cambio por fallback
ttlock_pin_id       — ID en BD tuyalaravel (mal nombrado, sirve para ambos providers)
dni_entregado       — bool, NO bloquea envío de claves (regla 30/04)
```

**HOTFIX 03/05** (`Reserva::$fillable`): añadidos `codigo_portal`,
`codigo_apartamento` y `codigo_fallback_enviado` que faltaban → `update()`
masivo los ignoraba en silencio.

**HOTFIX 03/05** mensajes de claves (3 sitios — `EnviarClavesChannexCommand`,
`Kernel.php`, `WhatsappController` IA): leer SIEMPRE `codigo_portal`
(canónico), nunca `codigo_acceso`. El mensaje incluye los DOS códigos
etiquetados claramente:

```
🔐 Acceso a tu apartamento

Código PORTAL del edificio: *XXXXXX* (pulsa # después)
Código APARTAMENTO (puerta del piso): *YYYY*
```

Soporte 7 idiomas: `es, en, fr, de, it, pt, ar`.

---

## 17. ENVÍO DE CLAVES — REGLA 30/04

**Las claves SIEMPRE se envían**, aunque falte DNI. Si falta DNI, se envía
ADEMÁS un aviso adicional (`dni_dia_entrada` template) con la URL para
subirlo:

```
https://crm.apartamentosalgeciras.com/dni-scanner/{token}
```

3 sitios en Kernel.php que tenían el bloqueo viejo (`if dni != true return false`)
fueron arreglados el 04/05 (commit `8e05927`).

---

## 18. CÁMARA DNI CON OVERLAY (vista `step1.blade.php`, 04/05)

Vista pública del check-in (`/dni-scanner/{token}` → `CheckInPublicController`).
Implementa cámara WebRTC con overlay SVG si el navegador lo soporta, y
fallback automático a `<input capture=environment>` si no:

- **Móviles modernos HTTPS**: cámara dentro del navegador, marco verde
  marcando dónde encajar el DNI horizontal (ratio 1.58:1)
- **Webviews limitados** (Booking app, Instagram, WhatsApp, etc.):
  detecta y cae al input file que abre la cámara nativa del SO
- **Validación post-captura**: si el aspect ratio no es horizontal,
  avisa al huésped antes de subir

Compatible con TODOS los móviles. Fallback siempre disponible.

---

## 19. IA — FALLBACK CLOUD→LOCAL (commit `f3d0301` del 02/05)

Hawkins AI tiene 2 modelos en producción:
- **Primario**: `gpt-oss:120b-cloud` (Ollama Cloud, rate limit semanal)
- **Secundario**: `gpt-oss:20b` (local en GPU 5090, sin rate limit)
- **Visión**: `qwen3-vl:8b-thinking` (cloud) → `qwen3-vl:8b` (local fallback)

Cuando el cloud agota cuota semanal devuelve `HTTP 502` con body
`{"error":"ollama_http","status":429,"detail":"weekly usage limit..."}`.

3 servicios con fallback automático cloud→local implementado:

| Servicio | Camino |
|---|---|
| `WebhookController::llamarHawkinsConFallback()` | Booking/Channex chat |
| `WhatsappController::hacerPeticionIALocal()` | WhatsApp huésped (drop-in) |
| `TranslationService::doTranslateRequest()` + `isCloudLimit()` | Traducciones |
| `App\Services\HawkinsAIHelper::chat()` | Helper centralizado para nuevos flujos |
| `App\Services\AIGatewayService::chatCompletion()` | Gateway oficial OpenAI→Hawkins |
| `App\Services\OpenAIVisionFallbackService` | Visión (DNI/facturas) |

**Variables de entorno**:
- `HAWKINS_AI_URL` (default `http://10.0.0.1:11435/`)
- `HAWKINS_AI_API_KEY`
- `HAWKINS_AI_CHAT_MODEL` (default `gpt-oss:120b-cloud`)
- `HAWKINS_AI_CHAT_FALLBACK_MODEL` (default `gpt-oss:20b`)
- `FALLBACK_VISION_MODEL_LOCAL` (default `qwen3-vl:8b-thinking`)
- `HAWKINS_WHATSAPP_AI` (default `gpt-oss:120b-cloud`)

Detección de cloud-limit (mismo en los 3 servicios): HTTP 429, o HTTP 502
con body que contiene `"weekly usage limit"`, `"weekly_limit"` o `"ollama_http"`.

---

## 20. APARTAMENTOS — clasificación tipo_uso (migración 30/04)

Los apartamentos tienen un campo `tipo_uso` en la tabla `apartamentos`:
- `apartamento` — uso normal turístico
- `zona_comun` — escaleras, oficina, lavandería (IDs 16-20)
- `test` — pruebas internas (IDs 22-23, ej. "Apartamento Planta Baja.A test")

Scope: `Apartamento::apartamentosReales()` filtra por `tipo_uso='apartamento'`.

---

## 21. DEPLOY workflow para `tuya-ttlocl-laravel` (servidor 217.160.39.79)

El proyecto tiene un **bind mount** especial: el código del contenedor
es directamente el host. Por eso el flujo es ligeramente distinto al CRM:

```bash
# 1. Editar local (en D:\proyectos\programasivan\tuya-ttlocl-laravel)
# 2. Subir al host
scp -i ~/.ssh/hawcert_server archivo.php \
    claude@217.160.39.79:/home/claude/tuyalaravel/app/app/Services/archivo.php

# 3. Lint dentro del contenedor (lee del bind mount, ya tiene los cambios)
ssh ... "docker exec -u www-data tuyalaravel-app php -l /var/www/app/Services/archivo.php"

# 4. NO hace falta reiniciar el contenedor (PHP-FPM relee el archivo en cada request)

# 5. Commit + push DESDE EL SERVIDOR (porque ahí está el repo git):
ssh ... "cd /home/claude/tuyalaravel/app && git add ... && git commit ... && git push"
```

**Si Permission denied al hacer git en el servidor**: hay archivos en
`.git/` con owner root por operaciones antiguas. Corregir con:
```bash
sudo chown -R claude:claude /home/claude/tuyalaravel/app/.git
```

**Si fileMode warnings al hacer git**: configurar el repo para ignorar mode bits:
```bash
git -C /home/claude/tuyalaravel/app config core.fileMode false
```

---

## 22. DEPLOY workflow para CRM `NuevoHeraAppartment`

NO tiene bind mount. Hay que copiar al contenedor explícitamente:

```bash
# 1. Editar local
# 2. Subir al host
scp ... archivo.php claude@217.160.39.79:/tmp/archivo.php

# 3. Backup forense + copy + ownership (rule #1: no romper PHP-FPM)
ssh ... "
  docker exec -u www-data laravel-f6irzmls5je67llxtivpv7lx \
    cp /var/www/html/app/X.php /var/www/html/app/X.php.bak.YYYYMMDD-descripcion;
  docker cp /tmp/archivo.php laravel-f6irzmls5je67llxtivpv7lx:/var/www/html/app/X.php;
  docker exec laravel-f6irzmls5je67llxtivpv7lx \
    chown www-data:www-data /var/www/html/app/X.php;
"

# 4. Lint
ssh ... "docker exec -u www-data laravel-f6irzmls5je67llxtivpv7lx php -l /var/www/html/app/X.php"

# 5. Limpiar caches si es blade/route/config
ssh ... "docker exec -u www-data laravel-f6irzmls5je67llxtivpv7lx php artisan view:clear"

# 6. Commit + push desde local
git add ... && git commit ... && git push origin main
```

---

## 23. SCRIPTS PHP DE DIAGNÓSTICO RÁPIDO

Plantilla mínima que carga Laravel:

```php
<?php
require "/var/www/html/vendor/autoload.php";   // CRM
// require "/var/www/vendor/autoload.php";     // tuyalaravel-app
$app = require "/var/www/html/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// ya puedes usar facade DB, modelos, etc.
$reserva = \App\Models\Reserva::find(6478);
echo $reserva->codigo_portal;
```

Ejecutar con `docker cp`:
```bash
scp script.php claude@servidor:/tmp/
ssh claude@servidor "docker cp /tmp/script.php CONTAINER:/tmp/script.php && docker exec -u www-data CONTAINER php /tmp/script.php"
```

---

## 24. CASOS HISTÓRICOS RELEVANTES (para no repetir errores)

### Mohamed Boubarkat (Suite 1B, 05/05) — PIN no funcionaba
- Tuya cloud devolvió `success: true` pero el comando `unlock_method_create`
  no se sincronizó al hardware
- **Fix sistémico**: hardening del 05/05 con verificación via logs type=5
  y reintentos. Ahora si pasa, el sistema activa fallback automáticamente.

### Isabel Maldonado (Suite 2B, 04/05) — entró sin claves del portal
- Subió el DNI tarde (después del cron 14:00)
- 3 sitios en Kernel.php tenían bloqueo `if !dni return false` → no
  reintentaban tras subir DNI
- **Fix**: 30/04 + 04/05 — claves se envían SIEMPRE, aviso DNI extra

### Malak Nechnach (Suite 1B, 03/05) — recibió clave equivocada
- `EnviarClavesChannexCommand:180` leía `codigo_acceso` (legacy) que tenía
  la CLAVE DEL PISO, no el código del portal
- **Fix**: leer `codigo_portal` (canónico) con fallback a `codigo_acceso`

### Saturación cerraduras (29/04) → 17 WhatsApp masivos por error
- `cerraduras:programar-proximas` programaba con 7 días de antelación
- Se llenaban los 9 slots con PINs futuros
- Llegaba el día y no había sitio para los actuales
- 3 fallos consecutivos → activó fallback → 17 huéspedes recibieron código
  emergencia que NO podían usar (cerradura saturada)
- **Regla nueva**: prohibido programar PINs con > 1 día de antelación

### iPoint alquiler atrasado distorsionando contabilidad
- En abril se pagaron €9.475 a iPoint que correspondían a alquileres
  de enero/febrero/marzo
- Eso hace parecer que apartamentos tienen pérdidas cuando realmente
  son ~€6.000 de beneficio mensual
- Análisis con `categoria_id=42` (ALQUILER A IPOINT) requiere mirar
  texto del concepto para detectar pagos retroactivos

---

_Este archivo debe actualizarse tras cada sesión importante. Cuando
añadas una feature nueva, añádela en §7. Cuando aprendas una regla
operacional, añádela en §8. Endpoints nuevos descubiertos van a §14.
Crons nuevos a §15._
