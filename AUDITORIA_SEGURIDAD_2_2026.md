# Auditoria de Seguridad #2 - Post-Fixes
Fecha: 13/04/2026

---

## CRITICO

### 1. Credenciales del MIR hardcodeadas en HomeController (activas)
- **Archivo:** `app/Http/Controllers/HomeController.php:297-298`
- **Problema:** El metodo `test()` contiene credenciales en texto plano del portal webpol.policia.es: usuario `H11070GEV04` y password `H4Kins4p4rtamento2023` en codigo activo (NO comentado, linea 298).
- **Riesgo:** CRITICO. Cualquiera con acceso al repo puede obtener credenciales del Ministerio del Interior. Ademas el metodo `test()` expone un `dd()` que vuelca datos al navegador.
- **Solucion:** Eliminar completamente el metodo `test()` de HomeController o reemplazar las credenciales por `env('MIR_USERNAME')` / `env('MIR_PASSWORD')` como ya se hizo en Kernel.php linea 1105-1106.

### 2. Credenciales del MIR en comentarios del codigo
- **Archivos:**
  - `app/Http/Controllers/HomeController.php:264-270` (comentado)
  - `app/Console/Kernel.php:1072-1078` (comentado)
- **Problema:** Aunque estan comentadas, las credenciales `H11070GEV04` / `H4Kins4p4rtamento2023` permanecen en el historial de git y en el codigo fuente.
- **Riesgo:** ALTO. Las credenciales son visibles para cualquiera que lea el codigo.
- **Solucion:** Eliminar todos los bloques comentados que contengan credenciales. Rotar la password del MIR inmediatamente.

### 3. Variables de entorno criticas ausentes en produccion
- **Servidor:** 217.160.39.79, contenedor `laravel-f6irzmls5je67llxtivpv7lx`
- **Problema:** Solo se encontro `STRIPE_WEBHOOK_SECRET=` (vacia) de las 4 variables buscadas. Faltan:
  - `MIR_USERNAME` - NO existe
  - `WHATSAPP_TOOLS_API_KEY` - NO existe
  - `CHANNEX_WEBHOOK_SECRET` - NO existe
- **Riesgo:** CRITICO.
  - Sin `WHATSAPP_TOOLS_API_KEY`: el middleware `CheckApiKey` comparara contra cadena vacia/null, potencialmente rechazando todas las peticiones legítimas O aceptando sin validar si la logica falla.
  - Sin `CHANNEX_WEBHOOK_SECRET`: los webhooks de Channex se aceptan sin validacion de firma (la verificacion se salta si `$expectedSecret` es falsy).
  - Sin `MIR_USERNAME`: el registro de viajeros no funciona.
- **Solucion:** Configurar todas las variables en el `.env` de produccion con valores reales.

### 4. Webhook de Channex sin validacion efectiva de firma
- **Archivos:** `app/Http/Controllers/ChannexController.php:19-27`, `app/Http/Controllers/WebhookController.php:39`
- **Problema:** La verificacion del webhook secret usa `env('CHANNEX_WEBHOOK_SECRET')`. Si la variable no esta configurada (como en produccion actualmente), el `if ($expectedSecret && ...)` se evalua como false y se acepta CUALQUIER peticion.
- **Riesgo:** CRITICO. Cualquiera puede enviar webhooks falsos al sistema, creando/modificando/cancelando reservas falsas.
- **Solucion:** 1) Configurar `CHANNEX_WEBHOOK_SECRET` en produccion. 2) Cambiar la logica para rechazar si el secret no esta configurado en lugar de aceptar.

---

## IMPORTANTE

### 5. Subida de archivos sin validacion de tipo/tamanio en multiples controladores
- **Archivos afectados:**
  - `app/Http/Controllers/PhotoController.php:112-125` - metodo `actualizar()`: Usa `getClientOriginalExtension()` sin validar mimes ni tamanio.
  - `app/Http/Controllers/GestionApartamentoController.php:2432-2435` - metodo `fotoRapida()`: `store()` sin validacion de tipo ni tamanio.
  - `app/Http/Controllers/CheckInPublicController.php:448-457` - metodo `saveUploadedImage()`: `move()` sin validacion de mimes ni tamanio.
  - `app/Http/Controllers/DNIScannerController.php:1115-1136` - metodo `guardarImagen()`: `move()` con `getClientOriginalExtension()` sin validar.
- **Problema:** Se aceptan archivos de cualquier tipo y tamanio. Un atacante podria subir un archivo PHP malicioso.
- **Riesgo:** ALTO. Posible ejecucion remota de codigo si el archivo se sube a una ruta accesible por el servidor web (ej: `public_path('images')`).
- **Solucion:** Agregar validacion con `$request->validate(['image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120'])` en cada punto de subida.

### 6. Mass assignment con $request->all() en multiples controladores
- **Archivos:**
  - `app/Http/Controllers/CategoryEmailController.php:47` - `$category->update($request->all())`
  - `app/Http/Controllers/Admin/ArticuloController.php:214` - `$articulo->update($request->all())`
  - `app/Http/Controllers/ConfiguracionesController.php:453` - `$persona->update($request->all())`
  - `app/Http/Controllers/ControlLimpiezaController.php:49` - `$control->update($request->all())`
  - `app/Http/Controllers/MetalicoController.php:174` - `$metalico->update($request->all())`
  - `app/Http/Controllers/StatusMailController.php:47` - `$status->update($request->all())`
- **Problema:** Se pasa todo el input del request directamente a `update()`. Aunque los modelos tienen `$fillable`, un atacante podria inyectar campos extra si el `$fillable` es demasiado permisivo.
- **Riesgo:** MEDIO. Depende de la configuracion de `$fillable` en cada modelo.
- **Solucion:** Usar `$request->validated()` o `$request->only([...])` en lugar de `$request->all()`.

### 7. Login de limpiadora sin rate limiting
- **Archivo:** `routes/web.php:1475` y `app/Http/Controllers/LimpiadoraLoginController.php`
- **Problema:** La ruta `POST /limpieza` no tiene middleware `throttle`. Ademas, la busqueda de usuario usa `whereRaw('LOWER(name) LIKE ?', ['%' ... '%'])` lo que permite ataques de fuerza bruta sin limitacion.
- **Riesgo:** MEDIO-ALTO. Permite ataques de fuerza bruta contra las cuentas de limpiadoras.
- **Solucion:** Agregar `->middleware('throttle:5,1')` a la ruta de login de limpiadora.

### 8. dd() activo en codigo de produccion
- **Archivo:** `app/Http/Controllers/HomeController.php:236`
- **Problema:** `dd($resultData)` activo (no comentado) en el metodo `test()`. Expone datos internos al navegador.
- **Riesgo:** MEDIO. Fuga de informacion interna.
- **Solucion:** Eliminar el metodo `test()` completo o al menos el `dd()`.

### 9. XSS potencial en templates de email
- **Archivos:**
  - `resources/views/emails/envioClavesEmail.blade.php:26` - `{!! $data !!}`
  - `resources/views/emails/despedidaEmail.blade.php:26` - `{!! $data !!}`
- **Problema:** Renderiza `$data` sin escapar. Si `$data` contiene HTML construido internamente es aceptable, pero si algun campo proviene de input de usuario sin sanitizar, podria inyectarse HTML/JS en el email.
- **Riesgo:** MEDIO. XSS en emails (email injection). El impacto depende del origen de `$data`.
- **Solucion:** Verificar que `$data` se construye siempre con contenido seguro. Considerar usar `{!! nl2br(e($data)) !!}` si es texto plano.

### 10. XSS potencial en paginas legales
- **Archivo:** `resources/views/public/pagina-legal/show.blade.php:238` - `{!! translate_dynamic($pagina->contenido) !!}`
- **Problema:** Renderiza contenido HTML de la BD sin escapar. Si un admin inyecta JS malicioso en el contenido de una pagina legal, se ejecutaria.
- **Riesgo:** BAJO-MEDIO. Requiere acceso admin para explotar, pero es un riesgo de XSS almacenado.
- **Solucion:** Usar un sanitizador HTML como HTMLPurifier antes de renderizar.

### 11. Middleware CheckApiKey usa env() directo en lugar de config()
- **Archivo:** `app/Http/Middleware/CheckApiKey.php:18`
- **Problema:** Usa `env('WHATSAPP_TOOLS_API_KEY')` directamente. En produccion con cache de configuracion (`php artisan config:cache`), las llamadas a `env()` devuelven `null`.
- **Riesgo:** MEDIO. Si se cachea la configuracion, el middleware fallaria y rechazaria TODAS las peticiones legitimas.
- **Solucion:** Agregar la variable a `config/services.php` y usar `config('services.whatsapp_tools.api_key')`.

### 12. ChannexController usa env() directo para webhook secret
- **Archivo:** `app/Http/Controllers/ChannexController.php:19`
- **Problema:** Mismo problema que CheckApiKey: `env('CHANNEX_WEBHOOK_SECRET')` devolvera null con config cacheada.
- **Solucion:** Mover a `config/services.php` y usar `config()`.

---

## MEJORA

### 13. Archivos sensibles en el repo (pero en .gitignore)
- **Archivos:** `cookies.txt`, `logs.log`, `main.py` existen en el directorio raiz.
- **Estado:** Estan listados en `.gitignore` y NO estan trackeados por git (verificado con `git ls-files`).
- **Recomendacion:** Eliminar estos archivos del directorio de trabajo si no son necesarios.

### 14. Session lifetime de 8 horas
- **Archivo:** `config/session.php:34` - `'lifetime' => env('SESSION_LIFETIME', 480)`
- **Problema:** Las sesiones duran 8 horas, lo cual es muy largo para una aplicacion con datos sensibles.
- **Recomendacion:** Reducir a 2-4 horas maximo, o implementar timeout por inactividad.

### 15. CORS permite todos los headers
- **Archivo:** `config/cors.php` - `'allowed_headers' => ['*']`
- **Problema:** Permite cualquier header en peticiones CORS. Los origenes estan bien restringidos a URLs especificas.
- **Recomendacion:** Restringir a headers necesarios: `['Content-Type', 'Authorization', 'X-Requested-With', 'X-API-Key', 'X-Scraper-Token']`.

### 16. Comentarios con dd() abundantes en controladores
- **Archivos:** Multiples controladores tienen `dd()` comentados (DNIController, ReservasController, ConfiguracionesController, etc.)
- **Problema:** Aunque estan comentados, ensucian el codigo y podrian descomentarse accidentalmente.
- **Recomendacion:** Eliminar todos los `dd()` comentados y usar logging apropiado.

### 17. SQL raw queries - sin inyeccion directa detectada
- **Archivos:** Se encontraron ~30 usos de `DB::raw`, `whereRaw`, `selectRaw`, `orderByRaw`.
- **Estado:** Ninguno usa input de usuario directamente. Todos usan constantes o parametros parametrizados.
- **Nota:** El `whereRaw` en `LimpiadoraLoginController.php:32` usa parametro parametrizado (`?`), lo cual es seguro.

---

## YA CORREGIDO (verificado)

### A. Credenciales del MIR en Kernel.php (PARCIALMENTE corregido)
- `app/Console/Kernel.php:1105-1106` usa `env('MIR_USERNAME')` y `env('MIR_PASSWORD')` correctamente.
- PERO las credenciales originales siguen en comentarios (lineas 1072-1078). Falta eliminar los comentarios.

### B. Demo/temp login routes eliminadas
- No se encontraron rutas `demo-`, `temp-`, ni `demo_` en `routes/web.php`.

### C. .env.example actualizado con variables requeridas
- Contiene: `MIR_USERNAME`, `MIR_PASSWORD`, `WORDPRESS_API_TOKEN`, `WHATSAPP_TOOLS_API_KEY`, `CHANNEX_WEBHOOK_SECRET`, `STRIPE_WEBHOOK_SECRET`.

### D. API routes protegidas con auth
- Rutas internas protegidas con `auth:sanctum`.
- Rutas WhatsApp protegidas con middleware `check.api.key`.
- Webhooks de Channex son publicos (necesario para webhooks) con verificacion de firma (aunque inefectiva sin secret configurado).
- Bankinter scraper usa token propio validado en el controller.
- Checkin completado tiene `throttle:10,1`.

### E. Middleware CheckApiKey registrado correctamente
- `app/Http/Kernel.php:76` registra `'check.api.key' => CheckApiKey::class`.
- Se aplica correctamente al grupo `whatsapp-tools` en `routes/api.php:82`.

### F. Session security correctamente configurada
- `config/session.php`: `encrypt=true`, `secure=true`, `http_only=true`, `same_site=lax`.

### G. Archivos sensibles no trackeados por git
- `cookies.txt`, `logs.log`, `main.py` estan en `.gitignore` y NO en el indice de git.

### H. SeoHelper escapa correctamente
- `app/Helpers/SeoHelper.php` usa `e()` para escapar todos los valores antes de renderizarlos en meta tags.

### I. Validacion de subida en varios controladores
- `PhotoController::store()` valida mimes y tamanio.
- `ApartamentosController` valida fotos con mimes y tamanio.
- `GastosController` e `IngresosController` validan tipo y tamanio.
- `HuespedesController` valida mimes y tamanio.
- `BankinterScraperApiController` valida mimes xls/xlsx y tamanio.
- `DiarioCajaController` valida mimes xls/xlsx y tamanio.
- `FacturasRecibidasController` valida mimes y tamanio.
- `MantenimientoIncidenciasController` valida mimes y tamanio.
- `GestionIncidenciasController` valida mimes y tamanio (via Validator).

---

## Resumen de Prioridades

| Prioridad | Hallazgo | Esfuerzo |
|-----------|----------|----------|
| CRITICO | #1 Credenciales MIR hardcodeadas activas | 5 min |
| CRITICO | #3 Variables .env ausentes en produccion | 10 min |
| CRITICO | #4 Channex webhooks sin validacion efectiva | 15 min |
| IMPORTANTE | #5 File uploads sin validacion (4 endpoints) | 30 min |
| IMPORTANTE | #6 Mass assignment $request->all() | 20 min |
| IMPORTANTE | #7 Login limpiadora sin rate limit | 5 min |
| IMPORTANTE | #11-12 env() directo en middleware/controller | 15 min |
