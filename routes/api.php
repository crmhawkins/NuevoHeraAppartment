<?php

use App\Http\Controllers\RatePlanController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Authenticated API Routes (auth:sanctum)
|--------------------------------------------------------------------------
|
| These routes require a valid Sanctum token. Clients must send:
| Authorization: Bearer <token>
|
*/
Route::middleware('auth:sanctum')->group(function () {

    // Rutas para proxy de IA Ollama
    Route::get('/ollama-proxy/health', [App\Http\Controllers\OllamaProxyController::class, 'health']);
    Route::post('/ollama-proxy/analyze-image', [App\Http\Controllers\OllamaProxyController::class, 'analyzeImage']);

    // API interna - Reservas, Apartamentos, Averias, Limpieza
    Route::post('/obtener-reservas-hoy', [App\Http\Controllers\Api\ApiController::class, 'obtenerReservasHoy'])->name('obtenerReservasHoy');
    Route::get('/obtener-apartamentos', [App\Http\Controllers\Api\ApiController::class, 'obtenerApartamentos'])->name('obtenerApartamentos');
    Route::get('/obtener-apartamentos-disponibles', [App\Http\Controllers\Api\ApiController::class, 'obtenerApartamentosDisponibles'])->name('obtenerApartamentosDisponibles');
    Route::post('/averias-tecnico', [App\Http\Controllers\Api\ApiController::class, 'averiasTecnico'])->name('averiasTecnico');
    Route::post('/equipo-limpieza', [App\Http\Controllers\Api\ApiController::class, 'equipoLimpieza'])->name('equipoLimpieza');
    Route::post('/apartamentos-disponibles', [App\Http\Controllers\Api\ApiController::class, 'obtenerApartamentosDisponibles'])->name('apartamentosDisponibles');
    Route::post('/agregar-compra-reserva', [App\Http\Controllers\Api\ApiController::class, 'agregarCompraReserva'])->name('agregarCompraReserva');
    Route::post('/agregar-reserva', [App\Http\Controllers\ReservasController::class, 'agregarReserva'])->name('api.reserva.agregar');

    // Edificios para integraciones externas
    Route::get('/edificios', [App\Http\Controllers\Api\ApiController::class, 'obtenerEdificios'])
        ->name('api.edificios.index');

    // Apartamentos activos en Channex (id_channex no nulo)
    Route::get('/apartamentos', [App\Http\Controllers\Api\ApiController::class, 'obtenerApartamentosChannex'])
        ->name('api.apartamentos.index');

    // Reservas para integraciones externas (con filtros)
    Route::get('/reservas', [App\Http\Controllers\Api\ApiController::class, 'obtenerReservas'])
        ->name('api.reservas.index');

    Route::get('/room-types/{propertyId}', [RatePlanController::class, 'getRoomTypes']);

    // Fotos cocina
    Route::post('/fotos-cocina-store/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'store'])->name('fotos.cocina-store');

    // Integracion Registro de Visitantes
    Route::post('/generar-link-checkin/{reservaId}', [App\Http\Controllers\Api\CheckinLinkController::class, 'generarLink'])->name('api.checkin.generar-link');

    // Analisis de fotos con OpenAI
    Route::post('/analyze-photo', [App\Http\Controllers\Api\PhotoAnalysisController::class, 'analyzePhoto'])->name('api.analyze-photo');
    Route::post('/mark-responsibility', [App\Http\Controllers\Api\ResponsibilityController::class, 'markResponsibility'])->name('api.mark-responsibility');
    Route::get('/photo-analysis/{id}', [App\Http\Controllers\Api\PhotoAnalysisDetailController::class, 'show'])->name('api.photo-analysis.show');
});

/*
|--------------------------------------------------------------------------
| WhatsApp Tools API Routes (external service - no auth)
|--------------------------------------------------------------------------
|
| Called by WhatsApp integration service. Protected by API key.
|
*/
Route::prefix('whatsapp-tools')->middleware('check.api.key')->group(function () {
    Route::post('/obtener-claves', [App\Http\Controllers\Api\WhatsappToolsController::class, 'obtenerClaves']);
    Route::post('/notificar-tecnico', [App\Http\Controllers\Api\WhatsappToolsController::class, 'notificarTecnico']);
    Route::post('/notificar-limpieza', [App\Http\Controllers\Api\WhatsappToolsController::class, 'notificarLimpieza']);
    Route::post('/verificar-disponibilidad', [App\Http\Controllers\Api\WhatsappToolsController::class, 'verificarDisponibilidad']);
    Route::post('/verificar-reserva', [App\Http\Controllers\Api\WhatsappToolsController::class, 'verificarReserva']);
});

/*
|--------------------------------------------------------------------------
| Webhook Routes (external services - no auth)
|--------------------------------------------------------------------------
|
| Called by Channex and other external services. No auth middleware.
| These must remain publicly accessible for webhooks to work.
|
*/

// Channex Webhooks (legacy - single endpoint)
Route::post('/channex', [App\Http\Controllers\ChannexController::class, 'webhook'])->name('channex.webhook');
Route::post('/ari-changes', [App\Http\Controllers\ChannexController::class, 'ariChanges'])->name('channex.ariChanges');
Route::post('/booking-any', [App\Http\Controllers\ChannexController::class, 'bookingAny'])->name('channex.bookingAny');
Route::post('/new-booking', [App\Http\Controllers\ChannexController::class, 'newBooking'])->name('channex.newBooking');
Route::post('/modification-booking', [App\Http\Controllers\ChannexController::class, 'modificationBooking'])->name('channex.modificationBooking');
Route::post('/cancellation-booking', [App\Http\Controllers\ChannexController::class, 'cancellationBooking'])->name('channex.cancellationBooking');
Route::post('/channel-sync-error', [App\Http\Controllers\ChannexController::class, 'channelSyncError'])->name('channex.channelSyncError');
Route::post('/reservation-request', [App\Http\Controllers\ChannexController::class, 'reservationRequest'])->name('channex.reservationRequest');
Route::post('/booking-unamapped-room', [App\Http\Controllers\ChannexController::class, 'bookingUnamappedRoom'])->name('channex.bookingUnamappedRoom');
Route::post('/booking-unamapped-rate', [App\Http\Controllers\ChannexController::class, 'bookingUnamappedRate'])->name('channex.bookingUnamappedRate');
Route::post('/sync-warning', [App\Http\Controllers\ChannexController::class, 'syncWarning'])->name('channex.syncWarning');
Route::post('/new-message', [App\Http\Controllers\ChannexController::class, 'newMessage'])->name('channex.newMessage');
Route::post('/new-review', [App\Http\Controllers\ChannexController::class, 'newReview'])->name('channex.newReview');
Route::post('/alteration-request', [App\Http\Controllers\ChannexController::class, 'alterationRequest'])->name('channex.alterationRequest');
Route::post('/airbnb-inquiry', [App\Http\Controllers\ChannexController::class, 'airbnbInquiry'])->name('channex.airbnbInquiry');
Route::post('/disconnect-channel', [App\Http\Controllers\ChannexController::class, 'disconnectChannel'])->name('channex.disconnectChannel');
Route::post('/disconnect-listing', [App\Http\Controllers\ChannexController::class, 'disconnectListing'])->name('channex.disconnectListing');
Route::post('/rate-error', [App\Http\Controllers\ChannexController::class, 'rateError'])->name('channex.rateError');
Route::post('/accepted-reservation', [App\Http\Controllers\ChannexController::class, 'acceptedReservation'])->name('channex.acceptedReservation');
Route::post('/decline-reservation', [App\Http\Controllers\ChannexController::class, 'declineReservation'])->name('channex.declineReservation');

// Channex Webhooks por Apartamento
Route::prefix('/webhooks')->group(function () {
    Route::post('{id}/ari-changes', [App\Http\Controllers\WebhookController::class, 'ariChanges'])->name('webhook.channex.ariChanges');
    Route::post('{id}/booking-any', [App\Http\Controllers\WebhookController::class, 'bookingAny'])->name('webhook.channex.bookingAny');
    Route::post('{id}/booking-unmapped-room', [App\Http\Controllers\WebhookController::class, 'bookingUnmappedRoom'])->name('webhook.channex.bookingAny');
    Route::post('{id}/booking-unmapped-rate', [App\Http\Controllers\WebhookController::class, 'bookingUnmappedRate'])->name('webhook.channex.bookingAny');
    Route::post('{id}/message', [App\Http\Controllers\WebhookController::class, 'message'])->name('webhook.channex.bookingAny');
    Route::post('{id}/review', [App\Http\Controllers\WebhookController::class, 'review'])->name('webhook.channex.bookingAny');
    Route::post('{id}/alteration_request', [App\Http\Controllers\WebhookController::class, 'alterationRequest'])->name('webhook.channex.bookingAny');
    Route::post('{id}/reservation-request', [App\Http\Controllers\WebhookController::class, 'reservationRequest'])->name('webhook.channex.bookingAny');
    Route::post('{id}/sync-error', [App\Http\Controllers\WebhookController::class, 'syncError'])->name('webhook.channex.syncWarning');
});

// Checkin completado - called by external registration service (no auth)
Route::post('/checkin-completado', [App\Http\Controllers\Api\CheckinLinkController::class, 'recibirDatos'])
    ->middleware('throttle:10,1')
    ->name('api.checkin.completado');

/*
|--------------------------------------------------------------------------
| Bankinter Scraper API (external service - token auth)
|--------------------------------------------------------------------------
|
| Endpoint que recibe el Excel exportado por el scraper Bankinter ejecutado
| en un PC externo (Windows). La autenticacion se realiza con un token
| compartido en la cabecera "X-Scraper-Token", validado dentro del
| controller con hash_equals (no usa Sanctum).
|
*/
Route::post('/bankinter/scraper/import', [App\Http\Controllers\Api\BankinterScraperApiController::class, 'import'])
    ->middleware('throttle:5,1')
    ->name('api.bankinter.scraper.import');

// Endpoint cifrado para que el PC externo obtenga las credenciales actualizadas.
// Auth: X-Scraper-Token header. Respuesta cifrada con AES-256-GCM.
Route::get('/bankinter/scraper/credentials', [App\Http\Controllers\Api\BankinterCredentialsApiController::class, 'index'])
    ->middleware('throttle:30,1')
    ->name('api.bankinter.scraper.credentials');
