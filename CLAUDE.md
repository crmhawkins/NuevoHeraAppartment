# NuevoHeraAppartment — Memoria del proyecto

Archivo de memoria local para retomar contexto en conversaciones futuras.
Última actualización: 2026-04-17.

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

_Este archivo debe actualizarse tras cada sesión importante. Cuando
añadas una feature nueva, añádela en §7. Cuando aprendas una regla
operacional, añádela en §8._
