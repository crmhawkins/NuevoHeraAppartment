# Auditoria de Seguridad y Estabilidad - Hawkins Suites CRM
Fecha: 12/04/2026
Auditor: Claude Opus 4.6

---

## CRITICO (arreglar inmediatamente)

### 1. Contrasena hardcodeada del sistema MIR en Kernel.php
- **Archivo:** `app/Console/Kernel.php:1106`
- **Problema:** Contrasena del portal MIR del Ministerio del Interior hardcodeada en texto plano: `'password' => 'HaKinsapartamento2024'`. Tambien hay una contrasena anterior comentada en linea 1077.
- **Riesgo:** Cualquier persona con acceso al repositorio Git (actual o historico) obtiene credenciales de acceso al sistema gubernamental de Registro de Viajeros. Esto es una brecha de datos critica que puede tener consecuencias legales.
- **Solucion recomendada:** Mover inmediatamente a `.env` como `MIR_PASSWORD=xxx` y usar `env('MIR_PASSWORD')` o `config('services.mir.password')`. Rotar la contrasena en el portal MIR. Revisar el historial de Git para limpiar la contrasena con `git filter-branch` o BFG Repo-Cleaner.

### 2. Token API de WordPress hardcodeado
- **Archivo:** `app/Console/Commands/ImportarReservasWordpress.php:17`
- **Problema:** Token de API de WordPress hardcodeado en texto plano: `$token = 't4fVqA3ZhGr6xBN...'` (64 caracteres). Este token permite leer y modificar reservas en el WordPress.
- **Riesgo:** Acceso no autorizado al sistema de reservas de WordPress. Cualquier persona con acceso al codigo puede manipular reservas.
- **Solucion recomendada:** Mover a `.env` como `WORDPRESS_CRM_TOKEN`. Rotar el token inmediatamente.

### 3. Credenciales MIR (usuario) hardcodeadas
- **Archivo:** `app/Console/Kernel.php:1105`
- **Problema:** Username del portal MIR hardcodeado: `'username' => 'H11070GEV04'`. Junto con la contrasena en la linea siguiente, da acceso completo al sistema.
- **Riesgo:** Acceso completo al sistema gubernamental MIR.
- **Solucion recomendada:** Mover a `.env` como `MIR_USERNAME`.

### 4. Endpoints API WhatsApp Tools sin autenticacion
- **Archivo:** `routes/api.php:83-89`
- **Problema:** 5 endpoints bajo `/api/whatsapp-tools/` no tienen ningun middleware de autenticacion: `obtener-claves`, `notificar-tecnico`, `notificar-limpieza`, `verificar-disponibilidad`, `verificar-reserva`. El propio codigo tiene un `TODO` reconociendo el problema.
- **Riesgo:** Cualquier persona puede:
  - Obtener claves de acceso a apartamentos con solo un codigo de reserva
  - Enviar notificaciones falsas a tecnicos y personal de limpieza
  - Verificar disponibilidad y datos de reservas sin autorizacion
  - Esto es especialmente critico porque `obtener-claves` expone codigos de puertas y edificios.
- **Solucion recomendada:** Agregar middleware de autenticacion por API key o token compartido. Minimo: validar un header `X-Api-Key` contra un secreto en `.env`.

### 5. Webhooks Channex sin verificacion de firma
- **Archivos:** `app/Http/Controllers/ChannexController.php:12`, `app/Http/Controllers/WebhookController.php:32`
- **Problema:** Ambos archivos tienen `TODO: Implement Channex webhook signature verification`. Los 20+ endpoints de webhook de Channex aceptan cualquier peticion POST sin verificar el origen.
- **Riesgo:** Un atacante puede enviar webhooks falsos para:
  - Crear reservas fantasma
  - Cancelar reservas reales
  - Modificar disponibilidad y precios
  - Inyectar mensajes falsos
- **Solucion recomendada:** Implementar verificacion de firma de Channex. Channex envia un header de firma que debe validarse.

### 6. Archivos sensibles en la raiz del repositorio
- **Archivos:**
  - `cookies.txt` - Contiene cookies de sesion del CRM (tokens XSRF y session cookies)
  - `logs.log` - Contiene logs de produccion con IPs, rutas del servidor, stack traces
  - `main.py` - Script de Selenium que automatiza Airbnb con el perfil Chrome del usuario
- **Problema:** Estos archivos no estan en `.gitignore` y contienen informacion sensible de produccion. `cookies.txt` tiene tokens de sesion validos. `logs.log` expone la estructura interna del servidor (`/var/www/vhosts/apartamentosalgeciras.com/`).
- **Riesgo:** Secuestro de sesion, reconnaissance del servidor para ataques dirigidos.
- **Solucion recomendada:** Eliminar estos archivos del repositorio inmediatamente. Agregarlos a `.gitignore`. Invalidar las sesiones expuestas.

### 7. Stripe Webhook con fallback sin verificacion de firma
- **Archivo:** `app/Http/Controllers/StripeWebhookController.php:40-43`
- **Problema:** Si el SDK de Stripe no esta disponible (`!class_exists('\Stripe\Webhook')`), el webhook procesa el payload sin verificar la firma: `$event = json_decode($payload, true)`. Esto permite que cualquier atacante envie webhooks falsos simulando pagos completados.
- **Riesgo:** Un atacante puede confirmar reservas sin pagar enviando un POST con `checkout.session.completed`. Perdida directa de dinero.
- **Solucion recomendada:** Eliminar el fallback. Si Stripe SDK no esta disponible, rechazar el webhook con error 500.

---

## IMPORTANTE (arreglar esta semana)

### 8. SSL verificacion deshabilitada en 20+ llamadas HTTP
- **Archivos:** Multiples archivos en `app/Http/Controllers/`, `app/Services/`, `app/Console/Commands/`
- **Problema:** `Http::withoutVerifying()` se usa en al menos 20 llamadas HTTP a la API de Channex, servicios de traduccion, y otros. `CURLOPT_SSL_VERIFYPEER = false` en MIRService.
- **Riesgo:** Vulnerable a ataques Man-in-the-Middle (MITM). Un atacante en la misma red puede interceptar tokens de API, datos de reservas, y credenciales bancarias.
- **Solucion recomendada:** Configurar correctamente los certificados CA. Para el MIR, descargar el certificado de la FNMT y agregarlo al bundle CA. Para Channex, no deberia ser necesario deshabilitar SSL.

### 9. Uso de `env()` directamente en controladores (cache rompida)
- **Archivos:** `ARIController.php:20-21`, `ChannexWebController.php:19-20`, `ReservaPagoController.php:28-29`, `DNIScannerController.php:529-530`, `InformeAiController.php:320`, `HomeController.php:207-210`, y 15+ archivos mas.
- **Problema:** Llamar a `env()` fuera de archivos de configuracion devuelve `null` cuando la configuracion esta cacheada (`php artisan config:cache`). Esto es un bug conocido de Laravel.
- **Riesgo:** En produccion con `config:cache` activo, todos los tokens de Channex, OpenAI, Azure, y Hawkins AI devuelven `null`, causando fallos silenciosos en reservas, pagos, y reconocimiento de DNI.
- **Solucion recomendada:** Mover todos los `env()` a archivos `config/services.php` y usar `config('services.xxx')` en el codigo.

### 10. Subida de archivos sin validacion de tipo en GastosController e IngresosController
- **Archivos:** `app/Http/Controllers/GastosController.php:96-99`, `app/Http/Controllers/IngresosController.php:91-94`
- **Problema:** Se sube el archivo con `getClientOriginalName()` sin validar extension ni MIME type. El nombre del archivo original se usa directamente: `$filename = time() . '_' . $file->getClientOriginalName()`.
- **Riesgo:** Un atacante puede subir un archivo PHP malicioso como "factura.php" que se almacena en `storage/app/public/facturas/`. Si el storage es accesible via web, se consigue ejecucion remota de codigo (RCE).
- **Solucion recomendada:** Agregar validacion: `'factura_foto' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120'`. Sanitizar el nombre del archivo.

### 11. Mass assignment con `$request->all()` en multiples controladores
- **Archivos:**
  - `Admin/ChecklistZonaComunController.php:49` - `ChecklistZonaComun::create($request->all())`
  - `Admin/ChecklistZonaComunController.php:96` - `$checklist->update($request->all())`
  - `Admin/ChecklistZonaComunController.php:168` - `$checklist->items()->create($request->all())`
  - `Admin/ArticuloController.php:112` - `Articulo::create($request->all())`
  - `Admin/ProveedorController.php:50` - `Proveedor::create($request->all())`
  - `Admin/ZonaComunController.php:49` - `ZonaComun::create($request->all())`
  - `AlertController.php:74` - `AlertService::create($request->all())`
  - `GrupoContabilidadController.php:88` - `GrupoContable::create($request->all())`
  - `Admin/AdminServiciosController.php:42,83` - `$data = $request->all()`
- **Problema:** Aunque los modelos tienen `$fillable`, usar `$request->all()` puede inyectar campos no esperados si `$fillable` no es perfectamente mantenido.
- **Riesgo:** Un atacante puede modificar campos que no deberia (ej: `user_id`, `is_admin`, `precio`, etc.) si se agrega un campo al modelo sin actualizar la validacion.
- **Solucion recomendada:** Usar `$request->validated()` despues de `validate()`, o `$request->only(['campo1', 'campo2'])`.

### 12. XSS potencial en vistas blade con datos de usuario
- **Archivos:** `resources/views/public/reservas/formulario-reserva.blade.php:313-403`
- **Problema:** Se usa `{!! ... !!}` (salida sin escapar) con datos de cliente: nombre, apellidos, email, telefono, DNI, direccion, localidad, etc. Ejemplo: `{!! $cliente->nombre ?? '<span>...' !!}`.
- **Riesgo:** Si un atacante introduce JavaScript en el campo nombre/email al crear una reserva via la API o Channex, ese script se ejecuta en el navegador de quien vea el formulario.
- **Solucion recomendada:** Usar `{{ $cliente->nombre ?? __('...') }}` con HTML de fallback separado usando `@if/@else`. Los datos de usuario NUNCA deben ir dentro de `{!! !!}`.

### 13. XSS en email templates con datos sin escapar
- **Archivos:** `resources/views/emails/envioClavesEmail.blade.php:26`, `resources/views/emails/despedidaEmail.blade.php:26`
- **Problema:** `{!! $data !!}` renderiza HTML sin escapar directamente en emails.
- **Riesgo:** Si `$data` contiene datos de usuario, un atacante puede inyectar HTML/JS en los emails enviados.
- **Solucion recomendada:** Sanitizar `$data` antes de pasarlo a la vista, o usar un purificador HTML como `htmlspecialchars()`.

### 14. XSS en paginas legales
- **Archivo:** `resources/views/public/pagina-legal/show.blade.php:238`
- **Problema:** `{!! translate_dynamic($pagina->contenido) !!}` renderiza contenido HTML de la base de datos sin sanitizar.
- **Riesgo:** Si un administrador compromete su cuenta o se inyecta contenido via SQL, se puede ejecutar JavaScript en paginas publicas.
- **Solucion recomendada:** Usar un purificador HTML como `HTMLPurifier` para sanitizar el contenido antes de renderizarlo.

### 15. Reservas: solapamiento se detecta pero no se previene
- **Archivos:** `app/Console/Commands/CheckOverlappingReservations.php`, `app/Services/ReservationOverlapService.php`
- **Problema:** El sistema detecta solapamientos cada minuto con un cron job y envia notificaciones por WhatsApp. Pero NO previene la creacion de reservas solapadas en el punto de creacion. Es reactivo, no preventivo.
- **Riesgo:** Se pueden crear reservas dobles. El tiempo entre la creacion y la deteccion (hasta 1 minuto) puede causar problemas de overbooking. Las reservas creadas via webhook de Channex tampoco validan solapamiento.
- **Solucion recomendada:** Agregar validacion de solapamiento en `ReservasController::agregarReserva()` y en `WebhookController` antes de crear la reserva, usando `lockForUpdate` para evitar condiciones de carrera.

### 16. Stripe webhook con `web` middleware incluye sesion innecesaria
- **Archivo:** `routes/web.php:160-162`
- **Problema:** La ruta del webhook de Stripe tiene `->middleware('web')` que incluye sesiones, cookies, y CSRF. Aunque CSRF esta excluido en VerifyCsrfToken, el middleware web es innecesario y puede causar problemas con sesiones.
- **Riesgo:** Los webhooks de Stripe podrian fallar por problemas de sesion/cookie. Deberia estar en `routes/api.php`.
- **Solucion recomendada:** Mover la ruta a `routes/api.php` con solo el middleware necesario.

---

## MEJORA (cuando sea posible)

### 17. Controladores excesivamente grandes (God Controllers)
- **Archivos:**
  - `GestionApartamentoController.php` - 3,398 lineas
  - `Kernel.php` - 3,097 lineas
  - `DNIScannerController.php` - 2,868 lineas
  - `WhatsappController.php` - 2,791 lineas
  - `ReservasController.php` - 2,193 lineas
- **Problema:** Estos controladores violan el principio de responsabilidad unica y son muy dificiles de mantener y testear.
- **Riesgo:** Errores dificiles de encontrar, imposible hacer code review efectivo, merge conflicts constantes.
- **Solucion recomendada:** Refactorizar extrayendo logica a Services (ya existe WhatsappNotificationService como buen ejemplo). El Kernel.php no deberia contener logica de negocio: mover a Commands separados.

### 18. `Apartamento::all()` y consultas sin paginacion
- **Archivos:** `ApartamentosController.php:31,52,69,90,98`, `ApiController.php:108,184,207`, `CategoryEmailController.php:12`, `ChecklistController.php:35`, y otros.
- **Problema:** Se usa `Model::all()` o `->get()` sin limitar resultados. Aunque actualmente la cantidad de datos puede ser manejable, esto no escala.
- **Riesgo:** Si el numero de apartamentos, reservas o amenities crece, las consultas sin paginacion causaran timeouts y problemas de memoria.
- **Solucion recomendada:** Usar `->paginate()` para listados. Para selects, usar `->select('id', 'nombre')->get()` para limitar columnas.

### 19. Falta de rate limiting en WhatsApp
- **Archivos:** `app/Http/Controllers/WhatsappController.php`, `app/Services/WhatsappNotificationService.php`, `app/Services/TecnicoNotificationService.php`
- **Problema:** No hay throttling explicito para llamadas a la API de WhatsApp Business. El servicio se llama desde multiples puntos (cron jobs, webhooks, acciones manuales).
- **Riesgo:** Se puede exceder el limite de la API de Meta, resultando en bloqueo temporal del numero de WhatsApp del negocio.
- **Solucion recomendada:** Implementar un rate limiter centralizado para llamadas a la API de WhatsApp, usando Laravel's `RateLimiter` o una cola con throttle.

### 20. Logs de produccion en archivo de proyecto
- **Archivo:** `logs.log` en la raiz del proyecto
- **Problema:** Contiene logs de produccion con informacion del servidor, IPs de webhook, rutas absolutas del servidor, y stack traces.
- **Riesgo:** Exposicion de informacion interna del servidor.
- **Solucion recomendada:** Eliminar el archivo. Agregar `logs.log` a `.gitignore`.

### 21. Uso inseguro de `getClientOriginalName()` en uploads
- **Archivos:** `GastosController.php:98,179`, `IngresosController.php:93,174`, `ApartamentosController.php:971`, `DNIController.php:1370,1379,1847`
- **Problema:** Se usa el nombre original del archivo proporcionado por el usuario para construir el nombre del archivo almacenado. Un atacante puede usar caracteres especiales o path traversal.
- **Riesgo:** Path traversal (`../../etc/passwd`), sobreescritura de archivos, o nombres que confunden al sistema.
- **Solucion recomendada:** Generar nombres aleatorios con `Str::uuid()` o `uniqid()` y mantener la extension original validada.

### 22. Archivo `main.py` - Script de scraping Airbnb en el repo
- **Archivo:** `main.py`
- **Problema:** Script de Selenium que automatiza la lectura de reservas de Airbnb usando el perfil de Chrome del usuario. Viola los ToS de Airbnb.
- **Riesgo:** Airbnb puede detectar la automatizacion y suspender la cuenta. El script depende del perfil Chrome local, no es portable.
- **Solucion recomendada:** Usar la API oficial de Airbnb via Channex en lugar de scraping.

### 23. Falta de indices en patrones de consulta frecuentes
- **Archivos:** Multiples controladores con `orderByRaw`, `whereRaw` complejos
- **Problema:** Consultas como `CASE WHEN prioridad = 'urgente' THEN 1...` en `MantenimientoDashboardController.php:47` y `MantenimientoIncidenciasController.php:31` no pueden aprovechar indices.
- **Riesgo:** Rendimiento degradado conforme crecen las tablas.
- **Solucion recomendada:** Considerar agregar un campo numerico `prioridad_orden` o usar indices funcionales.

### 24. Descargas temporales sin limite de descargas
- **Archivo:** `app/Http/Controllers/DescargaTemporalController.php`
- **Problema:** El controlador verifica expiracion y existencia, y llama a `incrementarDescargas()`, pero no hay limite maximo de descargas. El token puede usarse indefinidamente hasta que expire.
- **Riesgo:** Un enlace filtrado permite descargas ilimitadas de documentos de asesoria.
- **Solucion recomendada:** Agregar un campo `max_descargas` y verificarlo.

### 25. Datos sensibles potencialmente logueados
- **Archivos:** `app/Http/Middleware/LogUserActivity.php:115`
- **Problema:** `$data = $request->all()` se loguea, lo que puede incluir passwords, tokens, DNIs, y otros datos sensibles.
- **Riesgo:** Datos personales (GDPR/LOPD) en logs accesibles.
- **Solucion recomendada:** Filtrar campos sensibles antes de loguear: excluir `password`, `token`, `dni`, `num_identificacion`, etc.

### 26. Webhook de Stripe duplica logica (checkout vs payment_intent)
- **Archivo:** `app/Http/Controllers/StripeWebhookController.php`
- **Problema:** `handleCheckoutSessionCompleted` usa `lockForUpdate` y `DB::transaction` (correcto), pero `handlePaymentIntentSucceeded` no usa ninguno de los dos. Ademas, `handlePaymentIntentSucceeded` hace un `fullSync()` de Channex que es una operacion pesada.
- **Riesgo:** Condiciones de carrera en `handlePaymentIntentSucceeded`. El `fullSync()` puede tardar y bloquear la respuesta al webhook de Stripe (timeout).
- **Solucion recomendada:** Agregar `DB::transaction` con `lockForUpdate` en `handlePaymentIntentSucceeded`. Mover `fullSync()` a un job en cola.

### 27. VerifyCsrfToken excluye rutas amplias
- **Archivo:** `app/Http/Middleware/VerifyCsrfToken.php:14-21`
- **Problema:** Excluye `/channex/*`, `/webhook-handler`, `api/webhooks/*`, `api/whatsapp-tools/*`. Estas exclusiones son necesarias para webhooks pero el patron `api/whatsapp-tools/*` es demasiado amplio para endpoints sin autenticacion.
- **Riesgo:** Combinado con la falta de autenticacion en whatsapp-tools, facilita CSRF y ataques directos.
- **Solucion recomendada:** Agregar autenticacion a whatsapp-tools (ver punto 4).

---

## Resumen

| Severidad | Cantidad | Ejemplos clave |
|-----------|----------|---------------|
| CRITICO   | 7        | Contrasenas hardcodeadas, API sin auth, webhooks sin firma, archivos sensibles en repo |
| IMPORTANTE | 9       | SSL deshabilitado, env() en controladores, file upload sin validacion, XSS, mass assignment |
| MEJORA    | 11       | God controllers, falta paginacion, rate limiting WhatsApp, logs sensibles |

**Total: 27 hallazgos**

### Prioridades inmediatas (esta semana):
1. Rotar TODAS las credenciales expuestas (MIR, WordPress token)
2. Eliminar `cookies.txt`, `logs.log`, `main.py` del repositorio
3. Agregar autenticacion a endpoints WhatsApp Tools
4. Implementar verificacion de firma en webhooks Channex
5. Eliminar el fallback sin verificacion en Stripe webhook

### Notas positivas:
- El sistema de pagos Stripe usa `lockForUpdate` correctamente en el flujo principal
- La gestion de stock de amenities usa transacciones y locks correctamente
- Los modelos tienen `$fillable` definido (no hay `$guarded = []`)
- El check-in publico valida tipos de archivo y tamano correctamente
- El scraper de Bankinter tiene buena seguridad (token auth + cifrado AES-256-GCM)
- Las reservas de overbooking se detectan automaticamente (aunque reactivamente)
- La estructura de rutas separa bien publico/autenticado/admin
