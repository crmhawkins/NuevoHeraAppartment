<?php

use App\Http\Controllers\CategoryEmailController;
use App\Http\Controllers\CerrarApartamentoController;
use App\Http\Controllers\CuentasContableController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\GestionApartamentoController;
use App\Http\Controllers\GestionIncidenciasController;
use App\Http\Controllers\Admin\AdminIncidenciasController;
use App\Http\Controllers\Admin\BankinterConfigController;
use App\Http\Controllers\Admin\MonitorizacionDniController;
use App\Http\Controllers\Admin\ConflictosReservasController;
use App\Http\Controllers\MovimientosController;
use App\Http\Controllers\StatusMailController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\RatePlanController;
use App\Http\Controllers\RateUpdateController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\ARIController;
use App\Http\Controllers\AdminHolidaysController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\PresupuestoController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\MetalicoController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\WhatsappTemplateController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\ConfiguracionDescuentoController;
use App\Http\Controllers\ComandoDescuentoController;
use App\Http\Controllers\HistorialDescuentoController;
use App\Http\Controllers\NotificationController;
use App\Models\Cliente;
use App\Models\InvoicesStatus;
use App\Models\Reserva;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        // Redirigir según el rol del usuario
        if ($user->role === 'ADMIN') {
            return redirect('/admin');
        } elseif ($user->role === 'USER') {
            return redirect('/home');
        } elseif ($user->role === 'LIMPIEZA') {
            return redirect('/limpiadora/dashboard');
        } elseif ($user->role === 'MANTENIMIENTO') {
            return redirect('/mantenimiento/dashboard');
        } else {
            // Fallback para roles no reconocidos
            return redirect('/home');
        }
    }

    // Si no está autenticado, redirigir al portal web público
    return redirect()->route('web.index');
})->name('inicio.welcome');
// routes/web.php
Route::get('/whatsapp/mensajes/{remitente}', [WhatsappController::class, 'mensajes']);

Route::get('/calendario/apartamento/{id}.ics', [CalendarioController::class, 'ics'])->name('calendario.ics');

// ============================================
// RUTAS PÚBLICAS WEB (SIN AUTENTICACIÓN)
// ============================================
Route::prefix('web')->name('web.')->group(function () {
    // Cambio de idioma
    Route::get('/language/{locale}', [App\Http\Controllers\LanguageController::class, 'changeLanguage'])->name('language.change');

    // Página inicial del portal público
    Route::get('/', [App\Http\Controllers\PublicReservasController::class, 'index'])->name('index');

    // Lista de apartamentos
    Route::get('/apartamentos', [App\Http\Controllers\PublicReservasController::class, 'apartamentos'])->name('apartamentos');

    // Página Sobre Nosotros
    Route::get('/sobre-nosotros', [App\Http\Controllers\PublicReservasController::class, 'sobreNosotros'])->name('sobre-nosotros');

    // Página Contacto
    Route::get('/contacto', [App\Http\Controllers\PublicReservasController::class, 'contacto'])->name('contacto');
    Route::post('/contacto', [App\Http\Controllers\PublicReservasController::class, 'enviarContacto'])->name('contacto.enviar');

    // Página Video Exterior - Instrucciones de acceso
    Route::get('/video-exterior', [App\Http\Controllers\PublicReservasController::class, 'videoExterior'])->name('video-exterior');

    // Rutas de reservas públicas
    Route::prefix('reservas')->name('reservas.')->group(function () {
        Route::get('/buscador', [App\Http\Controllers\PublicReservasController::class, 'iframe'])->name('iframe');
        Route::get('/buscar', [App\Http\Controllers\PublicReservasController::class, 'buscar'])->name('buscar');
        Route::post('/buscar', [App\Http\Controllers\PublicReservasController::class, 'buscar'])->name('buscar.post');
        Route::get('/portal', [App\Http\Controllers\PublicReservasController::class, 'portal'])->name('portal');
        Route::get('/apartamento/{id}', [App\Http\Controllers\PublicReservasController::class, 'show'])->name('show');

        // Proceso de reserva y pago
        Route::get('/formulario/{apartamento}', [App\Http\Controllers\ReservaPagoController::class, 'formularioReserva'])->name('formulario');
        Route::post('/procesar', [App\Http\Controllers\ReservaPagoController::class, 'procesarReserva'])->name('procesar');
        Route::get('/pago/exito', [App\Http\Controllers\ReservaPagoController::class, 'exito'])->name('pago.exito');
        Route::get('/pago/cancelado', [App\Http\Controllers\ReservaPagoController::class, 'cancelado'])->name('pago.cancelado');
    });

    // Servicios y Extras
    Route::prefix('extras')->name('extras.')->group(function () {
        Route::get('/buscar', [App\Http\Controllers\ReservaExtrasController::class, 'buscarReserva'])->name('buscar');
        Route::post('/buscar', [App\Http\Controllers\ReservaExtrasController::class, 'mostrarServicios'])->name('mostrar-servicios');
        Route::post('/comprar', [App\Http\Controllers\ReservaExtrasController::class, 'procesarCompra'])->name('comprar');
        Route::get('/pago/exito', [App\Http\Controllers\ReservaExtrasController::class, 'exito'])->name('pago.exito');
        Route::get('/pago/cancelado', [App\Http\Controllers\ReservaExtrasController::class, 'cancelado'])->name('pago.cancelado');
    });

    // Página de servicios
    Route::get('/servicios', [App\Http\Controllers\ServiciosController::class, 'index'])->name('servicios');
    Route::get('/servicios/{servicio}/reserva-rango', [App\Http\Controllers\ServiciosController::class, 'reservaRango'])->name('servicios.reserva-rango');
    Route::post('/servicios/{servicio}/comprobar-disponibilidad', [App\Http\Controllers\ServiciosController::class, 'comprobarDisponibilidad'])->name('servicios.comprobar-disponibilidad');

    // Política de Cancelaciones
    Route::get('/politica-cancelaciones', [App\Http\Controllers\PublicPoliticaCancelacionController::class, 'index'])->name('politica-cancelaciones');

    // Páginas Legales
    Route::get('/pagina-legal/{slug}', [App\Http\Controllers\PublicPaginaLegalController::class, 'show'])->name('pagina-legal.show');

    // Preguntas Frecuentes
    Route::get('/preguntas-frecuentes', [App\Http\Controllers\PublicPreguntasFrecuentesController::class, 'index'])->name('preguntas-frecuentes');

    // Autenticación Pública (Clientes)
    Route::get('/login', [App\Http\Controllers\PublicAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\PublicAuthController::class, 'login']);
    Route::get('/register', [App\Http\Controllers\PublicAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [App\Http\Controllers\PublicAuthController::class, 'register']);
    Route::get('/verificar-cuenta', [App\Http\Controllers\PublicAuthController::class, 'showVerificarCuentaForm'])->name('verificar-cuenta');
    Route::post('/verificar-cuenta', [App\Http\Controllers\PublicAuthController::class, 'verificarCuenta']);
    Route::get('/establecer-password', [App\Http\Controllers\PublicAuthController::class, 'showEstablecerPasswordForm'])->name('establecer-password');
    Route::post('/establecer-password', [App\Http\Controllers\PublicAuthController::class, 'establecerPassword']);
    Route::post('/logout', [App\Http\Controllers\PublicAuthController::class, 'logout'])->name('logout');

    // Perfil de Cliente (requiere autenticación)
    Route::middleware('auth:cliente')->group(function () {
        Route::get('/perfil', [App\Http\Controllers\PublicPerfilController::class, 'index'])->name('perfil');
        Route::put('/perfil', [App\Http\Controllers\PublicPerfilController::class, 'updatePerfil'])->name('perfil.update');
        Route::put('/perfil/password', [App\Http\Controllers\PublicPerfilController::class, 'updatePassword'])->name('perfil.password');
        Route::post('/perfil/metodo-pago', [App\Http\Controllers\PublicPerfilController::class, 'guardarMetodoPago'])->name('perfil.metodo-pago');
        Route::delete('/perfil/metodo-pago/{paymentMethodId}', [App\Http\Controllers\PublicPerfilController::class, 'eliminarMetodoPago'])->name('perfil.metodo-pago.delete');
        Route::get('/perfil/reserva/{id}', [App\Http\Controllers\PublicPerfilController::class, 'showReserva'])->name('perfil.reserva.show');
    });

    // Webhook de Stripe (sin CSRF)
    Route::post('/webhooks/stripe', [App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])
        ->middleware('web')
        ->name('webhooks.stripe');

    // Aquí puedes añadir más rutas públicas en el futuro
    // Ejemplo:
    // Route::prefix('contacto')->name('contacto.')->group(function () {
    //     Route::get('/', [ContactoController::class, 'index'])->name('index');
    //     Route::post('/enviar', [ContactoController::class, 'enviar'])->name('enviar');
    // });
});

// Rutas legacy para compatibilidad (redirigen a las nuevas rutas web)
Route::prefix('reservas-publicas')->name('public.reservas.')->group(function () {
    Route::get('/buscador', function () {
        return redirect()->route('web.reservas.iframe');
    })->name('iframe');
    Route::get('/buscar', function () {
        return redirect()->route('web.reservas.buscar', request()->all());
    })->name('buscar');
    Route::post('/buscar', [App\Http\Controllers\PublicReservasController::class, 'buscar'])->name('buscar.post');
    Route::get('/portal', function () {
        return redirect()->route('web.reservas.portal', request()->all());
    })->name('portal');
    Route::get('/apartamento/{id}', function ($id) {
        return redirect()->route('web.reservas.show', ['id' => $id] + request()->all());
    })->name('show');
});

// Redirigir rutas incorrectas web/reservas-publicas/* a web/reservas/*
Route::prefix('web/reservas-publicas')->group(function () {
    Route::get('/', function () {
        return redirect()->route('web.reservas.portal', request()->all());
    });
    Route::get('/apartamento/{id}', function ($id) {
        return redirect()->route('web.reservas.show', ['id' => $id] + request()->all());
    });
    Route::get('/buscador', function () {
        return redirect()->route('web.reservas.iframe');
    });
    Route::get('/buscar', function () {
        return redirect()->route('web.reservas.buscar', request()->all());
    });
    Route::get('/portal', function () {
        return redirect()->route('web.reservas.portal', request()->all());
    });
});

Route::get('/regenerate-invoices', [App\Http\Controllers\InvoicesController::class, 'regenerateInvoicesForOctober']);
Route::get('/registrar-webhooks/{id}', [App\Http\Controllers\ApartamentosController::class, 'registrarWebhooks']);

Route::get('/request-data', function (Request $request) {
    return $request->all(); // Esto devolverá todos los datos de la solicitud
});
Route::get('paises', [App\Http\Controllers\HomeController::class, 'paises'])->name('paises');
Route::get('tipos', [App\Http\Controllers\HomeController::class, 'tipos'])->name('tipos');

// Redirección de /video-exterior a /web/video-exterior
Route::get('/video-exterior', function () {
    return redirect()->route('web.video-exterior');
});
Route::get('pruebas-dni', [App\Http\Controllers\HomeController::class, 'pruebas'])->name('pruebas');
Route::get('/get-reservas-json', [App\Http\Controllers\HomeController::class, 'getReservas'])->name('reservas.get.json');

Auth::routes();

// HawCert: autenticación por certificado
Route::prefix('auth/hawcert')->name('hawcert.')->group(function () {
    Route::post('/login-with-certificate', [App\Http\Controllers\Auth\HawCertAuthController::class, 'loginWithCertificate'])->name('login-with-certificate');
    Route::post('/validate-certificate', [App\Http\Controllers\Auth\HawCertAuthController::class, 'validateCertificate'])->name('validate-certificate');
    Route::post('/validate-key', [App\Http\Controllers\Auth\HawCertAuthController::class, 'validateKey'])->name('validate-key');
    Route::get('/callback', [App\Http\Controllers\Auth\HawCertAuthController::class, 'callback'])->name('callback');
});


// Subida publica de facturas desde movil por los trabajadores.
// El token forma parte de la URL (services.facturas.upload_token) y se valida
// en el controller con hash_equals. Rate-limit 20 req/min/IP.
Route::middleware(['throttle:20,1'])->group(function () {
    Route::get('/facturas/subir/{token}', [App\Http\Controllers\FacturaUploadMovilController::class, 'show'])
        ->name('facturas.subir.show');
    Route::post('/facturas/subir/{token}', [App\Http\Controllers\FacturaUploadMovilController::class, 'store'])
        ->name('facturas.subir.store');

    // [2026-04-17] Descarga publica de factura por token (cliente recibe el
    // enlace por WhatsApp/email). No requiere auth. El token caduca a 30 dias
    // y queda registrado cuando se descarga.
    Route::get('/facturas/descargar/{token}', [App\Http\Controllers\InvoicePublicDownloadController::class, 'download'])
        ->name('facturas.descargarPublica');
});

// Rutas de admin
Route::middleware(['auth', 'role:ADMIN'])->group(function () {

    Route::get('/admin', [App\Http\Controllers\DashboardController::class, 'index'])->name('inicio');

    // Rutas para Informes AI
    Route::get('/informes-ai', [App\Http\Controllers\InformeAiController::class, 'index'])->name('informes.ai.index');
    Route::post('/informe-ai/generar', [App\Http\Controllers\InformeAiController::class, 'generarInforme'])->name('informe.ai.generar');
    Route::get('/informe-ai/{id}', [App\Http\Controllers\InformeAiController::class, 'verInforme'])->name('informe.ai.ver');
    Route::delete('/informe-ai/{id}/eliminar', [App\Http\Controllers\InformeAiController::class, 'eliminar'])->name('informe.ai.eliminar');

    Route::resource('metalicos', MetalicoController::class);
    Route::get('/metalico/create-gasto', [App\Http\Controllers\MetalicoController::class, 'createGasto'])->name('metalicos.createGasto');
    Route::post('/metalico/store', [App\Http\Controllers\MetalicoController::class, 'storeGasto'])->name('metalicos.storeGasto');

    // Apartamentos
    Route::get('/apartamentos', [App\Http\Controllers\ApartamentosController::class, 'indexAdmin'])->name('apartamentos.admin.index');
    Route::get('/apartamentos/create', [App\Http\Controllers\ApartamentosController::class, 'createAdmin'])->name('apartamentos.admin.create');
    Route::get('/apartamentos/{id}', [App\Http\Controllers\ApartamentosController::class, 'showAdmin'])->name('apartamentos.admin.show');
    Route::get('/apartamentos/{id}/estadisticas', [App\Http\Controllers\ApartamentosController::class, 'estadisticasAdmin'])->name('apartamentos.admin.estadisticas');
    Route::get('/apartamentos/{id}/edit', [App\Http\Controllers\ApartamentosController::class, 'editAdmin'])->name('apartamentos.admin.edit');
    Route::post('/apartamentos/store', [App\Http\Controllers\ApartamentosController::class, 'storeAdmin'])->name('apartamentos.admin.store');
    Route::put('/apartamentos/{id}/update', [App\Http\Controllers\ApartamentosController::class, 'updateAdmin'])->name('apartamentos.admin.update');
    Route::post('/apartamentos/{id}/destroy', [App\Http\Controllers\ApartamentosController::class, 'destroy'])->name('apartamentos.admin.destroy');

    // Rutas para gestión de fotos
    Route::post('/apartamentos/{id}/photos/upload', [App\Http\Controllers\ApartamentosController::class, 'uploadPhotos'])->name('apartamentos.photos.upload');
    Route::delete('/apartamentos/{id}/photos/{photoId}', [App\Http\Controllers\ApartamentosController::class, 'deletePhoto'])->name('apartamentos.photos.delete');
    Route::post('/apartamentos/{id}/photos/{photoId}/primary', [App\Http\Controllers\ApartamentosController::class, 'setPrimaryPhoto'])->name('apartamentos.photos.primary');

    // Servicios
    Route::resource('servicios', App\Http\Controllers\Admin\AdminServiciosController::class)->names([
        'index' => 'admin.servicios.index',
        'create' => 'admin.servicios.create',
        'store' => 'admin.servicios.store',
        'show' => 'admin.servicios.show',
        'edit' => 'admin.servicios.edit',
        'update' => 'admin.servicios.update',
        'destroy' => 'admin.servicios.destroy',
    ]);

    // Política de Cancelaciones
    Route::get('/politica-cancelacion/edit', [App\Http\Controllers\Admin\PoliticaCancelacionController::class, 'edit'])->name('admin.politica-cancelacion.edit');
    Route::put('/politica-cancelacion', [App\Http\Controllers\Admin\PoliticaCancelacionController::class, 'update'])->name('admin.politica-cancelacion.update');

    // Páginas Legales
    Route::resource('paginas-legales', App\Http\Controllers\Admin\PaginasLegalesController::class)->names([
        'index' => 'admin.paginas-legales.index',
        'create' => 'admin.paginas-legales.create',
        'store' => 'admin.paginas-legales.store',
        'show' => 'admin.paginas-legales.show',
        'edit' => 'admin.paginas-legales.edit',
        'update' => 'admin.paginas-legales.update',
        'destroy' => 'admin.paginas-legales.destroy',
    ]);

    // Preguntas Frecuentes
    Route::resource('preguntas-frecuentes', App\Http\Controllers\Admin\PreguntasFrecuentesController::class)->names([
        'index' => 'admin.preguntas-frecuentes.index',
        'create' => 'admin.preguntas-frecuentes.create',
        'store' => 'admin.preguntas-frecuentes.store',
        'show' => 'admin.preguntas-frecuentes.show',
        'edit' => 'admin.preguntas-frecuentes.edit',
        'update' => 'admin.preguntas-frecuentes.update',
        'destroy' => 'admin.preguntas-frecuentes.destroy',
    ]);

    // Servicios Técnicos
    Route::prefix('admin/servicios-tecnicos')->name('admin.servicios-tecnicos.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ServiciosTecnicosController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\ServiciosTecnicosController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\ServiciosTecnicosController::class, 'store'])->name('store');
        Route::get('/{serviciosTecnico}/edit', [App\Http\Controllers\Admin\ServiciosTecnicosController::class, 'edit'])->name('edit');
        Route::put('/{serviciosTecnico}', [App\Http\Controllers\Admin\ServiciosTecnicosController::class, 'update'])->name('update');
        Route::delete('/{serviciosTecnico}', [App\Http\Controllers\Admin\ServiciosTecnicosController::class, 'destroy'])->name('destroy');

        // Rutas para categorías
        Route::post('/categorias', [App\Http\Controllers\Admin\ServiciosTecnicosController::class, 'storeCategoria'])->name('storeCategoria');
        Route::put('/categorias/{categoria}', [App\Http\Controllers\Admin\ServiciosTecnicosController::class, 'updateCategoria'])->name('updateCategoria');
        Route::delete('/categorias/{categoria}', [App\Http\Controllers\Admin\ServiciosTecnicosController::class, 'destroyCategoria'])->name('destroyCategoria');
    });

    // Técnicos y Servicios (Asignación de precios)
    Route::prefix('admin/tecnicos-servicios')->name('admin.tecnicos-servicios.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\TecnicosServiciosController::class, 'index'])->name('index');
        Route::get('/{tecnicoId}', [App\Http\Controllers\Admin\TecnicosServiciosController::class, 'show'])->name('show');
        Route::post('/{tecnicoId}', [App\Http\Controllers\Admin\TecnicosServiciosController::class, 'store'])->name('store');
        Route::delete('/{tecnicoId}/{servicioId}', [App\Http\Controllers\Admin\TecnicosServiciosController::class, 'destroy'])->name('destroy');
    });

    // Subcontrataciones (vista unificada de Técnicos + Servicios + Asignación)
    Route::get('admin/subcontrataciones', [App\Http\Controllers\Admin\SubcontratacionesController::class, 'index'])->name('admin.subcontrataciones.index');

    // Normas de la Casa
    Route::resource('normas-casa', App\Http\Controllers\Admin\AdminNormasController::class)->names([
        'index' => 'admin.normas-casa.index',
        'create' => 'admin.normas-casa.create',
        'store' => 'admin.normas-casa.store',
        'show' => 'admin.normas-casa.show',
        'edit' => 'admin.normas-casa.edit',
        'update' => 'admin.normas-casa.update',
        'destroy' => 'admin.normas-casa.destroy',
    ]);

    // Lugares Cercanos (nested bajo apartamentos)
    Route::prefix('apartamentos/{apartamentoId}/lugares-cercanos')->name('admin.lugares-cercanos.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AdminLugaresCercanosController::class, 'index'])->name('index');
        Route::delete('/borrar-todos', [App\Http\Controllers\Admin\AdminLugaresCercanosController::class, 'borrarTodos'])->name('borrar-todos');
        Route::get('/generar-automatico', [App\Http\Controllers\Admin\AdminLugaresCercanosController::class, 'mostrarGeneracionAutomatica'])->name('generar-automatico');
        Route::post('/generar-automaticamente', [App\Http\Controllers\Admin\AdminLugaresCercanosController::class, 'generarAutomaticamente'])->name('generar-automaticamente');
        Route::get('/create', [App\Http\Controllers\Admin\AdminLugaresCercanosController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\AdminLugaresCercanosController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [App\Http\Controllers\Admin\AdminLugaresCercanosController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\Admin\AdminLugaresCercanosController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\Admin\AdminLugaresCercanosController::class, 'destroy'])->name('destroy');
    });

    // FAQs de Apartamentos (nested bajo apartamentos)
    Route::prefix('apartamentos/{apartamentoId}/faq-apartamentos')->name('admin.faq-apartamentos.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AdminFaqApartamentosController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\AdminFaqApartamentosController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\AdminFaqApartamentosController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [App\Http\Controllers\Admin\AdminFaqApartamentosController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\Admin\AdminFaqApartamentosController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\Admin\AdminFaqApartamentosController::class, 'destroy'])->name('destroy');
    });

    // Tarifas
    Route::resource('tarifas', TarifaController::class);
    Route::post('/tarifas/{tarifa}/toggle-status', [TarifaController::class, 'toggleStatus'])->name('tarifas.toggle-status');
    Route::post('/tarifas/{tarifa}/asignar-apartamento', [TarifaController::class, 'asignarApartamento'])->name('tarifas.asignar-apartamento');
    Route::post('/tarifas/{tarifa}/desasignar-apartamento', [TarifaController::class, 'desasignarApartamento'])->name('tarifas.desasignar-apartamento');

    // Configuración de Descuentos
    Route::resource('configuracion-descuentos', ConfiguracionDescuentoController::class);
    Route::post('/configuracion-descuentos/{configuracionDescuento}/toggle-status', [ConfiguracionDescuentoController::class, 'toggleStatus'])->name('configuracion-descuentos.toggle-status');

    // Comandos de Descuento
    Route::post('/admin/ejecutar-comando-descuentos', [ComandoDescuentoController::class, 'ejecutarComando'])->name('admin.ejecutar-comando-descuentos');

    // Cupones de Descuento
    Route::prefix('cupones')->name('admin.cupones.')->group(function () {
        Route::get('/', [App\Http\Controllers\CuponController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\CuponController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\CuponController::class, 'store'])->name('store');
        Route::get('/{cupon}', [App\Http\Controllers\CuponController::class, 'show'])->name('show');
        Route::get('/{cupon}/edit', [App\Http\Controllers\CuponController::class, 'edit'])->name('edit');
        Route::put('/{cupon}', [App\Http\Controllers\CuponController::class, 'update'])->name('update');
        Route::delete('/{cupon}', [App\Http\Controllers\CuponController::class, 'destroy'])->name('destroy');
        Route::patch('/{cupon}/toggle-activo', [App\Http\Controllers\CuponController::class, 'toggleActivo'])->name('toggle-activo');
        Route::post('/{cupon}/duplicate', [App\Http\Controllers\CuponController::class, 'duplicate'])->name('duplicate');
    });

// Rutas para historial de descuentos
Route::get('/admin/historial-descuentos', [HistorialDescuentoController::class, 'index'])->name('admin.historial-descuentos.index');
Route::get('/admin/historial-descuentos/{historial}', [HistorialDescuentoController::class, 'show'])->name('admin.historial-descuentos.show');
Route::get('/admin/historial-descuentos/{historial}/datos-momento', [HistorialDescuentoController::class, 'getDatosMomento'])->name('admin.historial-descuentos.datos-momento');


    Route::post('/upload-excel', [MovimientosController::class, 'uploadExcel'])->name('upload.excel');
    Route::post('/upload-csv-booking', [MovimientosController::class, 'uploadCSV'])->name('upload.csvBooking');
    Route::get('/upload-files', [MovimientosController::class, 'uploadFiles'])->name('admin.upload.files');
    Route::get('/upload-files-booking', [MovimientosController::class, 'uploadBooking'])->name('admin.uploadBooking.files');

    // Limpieza
    Route::get('aparatamento-limpieza/{id}/show', [\App\Http\Controllers\ApartamentoLimpiezaController::class, 'show'])->name('apartamentoLimpieza.admin.show');

    // Incidencias
    Route::resource('admin/incidencias', AdminIncidenciasController::class)->names('admin.incidencias');
    Route::post('/admin/incidencias/{incidencia}/resolver', [AdminIncidenciasController::class, 'resolver'])->name('admin.incidencias.resolver');
    Route::post('/admin/incidencias/{incidencia}/cambiar-prioridad', [AdminIncidenciasController::class, 'cambiarPrioridad'])->name('admin.incidencias.cambiar-prioridad');
    Route::post('/admin/incidencias/{incidencia}/notificar-tecnicos', [AdminIncidenciasController::class, 'notificarTecnicos'])->name('admin.incidencias.notificar-tecnicos');
    Route::get('/admin/incidencias-pendientes', [AdminIncidenciasController::class, 'getPendientes'])->name('admin.incidencias.pendientes');

    // Alertas del Sistema
    Route::get('/admin/alerts', function() {
        return view('admin.alerts.index');
    })->name('admin.alerts.index');

    // Reservas
    // Route::get('/reservas', [App\Http\Controllers\ReservasController::class, 'index'])->name('reservas.index');

    // Clientes
    Route::get('/clientes', [App\Http\Controllers\ClientesController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/create', [App\Http\Controllers\ClientesController::class, 'create'])->name('clientes.create');
    Route::get('/clientes/{cliente}', [App\Http\Controllers\ClientesController::class, 'show'])->name('clientes.show');
    Route::post('/cliente/store', [App\Http\Controllers\ClientesController::class, 'store'])->name('clientes.store');
    Route::get('/cliente/edit/{id}', [App\Http\Controllers\ClientesController::class, 'edit'])->name('clientes.edit');
    Route::put('/cliente/update/{id}', [App\Http\Controllers\ClientesController::class, 'update'])->name('clientes.update');
    Route::post('/cliente/destroy/{id}', [App\Http\Controllers\ClientesController::class, 'destroy'])->name('clientes.destroy');

    // Reservas
    Route::get('/reservas', [App\Http\Controllers\ReservasController::class, 'index'])->name('reservas.index');
    Route::get('/reservas/{reserva}/show', [App\Http\Controllers\ReservasController::class, 'show'])->name('reservas.show');
    Route::post('/reservas/{reserva}/enviar-mir', [App\Http\Controllers\ReservasController::class, 'enviarMIR'])->name('reservas.enviar-mir');
    Route::post('/reservas/{reserva}/enviar-plataforma', [App\Http\Controllers\ReservasController::class, 'enviarPlataforma'])->name('reservas.enviar-plataforma');
    Route::post('/reservas/{reserva}/toggle-conversacion-plataforma', [App\Http\Controllers\ReservasController::class, 'toggleConversacionPlataforma'])->name('reservas.toggle-conversacion-plataforma');
    Route::get('/reservas/create', [App\Http\Controllers\ReservasController::class, 'create'])->name('reservas.create');
    Route::post('/reservas/store', [App\Http\Controllers\ReservasController::class, 'store'])->name('reservas.store');
    Route::post('/reservas/update/{id}', [App\Http\Controllers\ReservasController::class, 'update'])->name('reservas.update');
    Route::get('/get-reservas', [App\Http\Controllers\ReservasController::class, 'getReservas'])->name('reservas.get');
    Route::get('/get-apartamentos-ocupacion', [App\Http\Controllers\ReservasController::class, 'getApartamentosOcupacion'])->name('apartamentos.ocupacion');
    Route::get('/get-room-types/{apartamento_id}', [App\Http\Controllers\ReservasController::class, 'getRoomTypes']);
    Route::get('reservas-calendar', [App\Http\Controllers\ReservasController::class, 'calendar'])->name('reservas.calendar');
    Route::put('/reservas/{id}', [App\Http\Controllers\ReservasController::class, 'updateReserva'])->name('reservas.updateReserva');
    Route::get('/reservas/{reserva}/edit', [App\Http\Controllers\ReservasController::class, 'edit'])->name('reservas.edit');
    Route::delete('/reservas/{id}', [App\Http\Controllers\ReservasController::class, 'destroy'])->name('reservas.destroy');
    Route::post('/reservas/{id}/restore', [App\Http\Controllers\ReservasController::class, 'restore'])->name('reservas.restore');

    // Panel de accesos TTLock
    Route::get('/accesos', [\App\Http\Controllers\AccesosController::class, 'index'])->name('accesos.index');
    Route::post('/accesos/{reservaId}/regenerar', [\App\Http\Controllers\AccesosController::class, 'regenerarCodigo'])->name('accesos.regenerar');

    // Cerrar Apartamento
    Route::get('/cerrar-apartamento', [CerrarApartamentoController::class, 'index'])->name('admin.cerrar-apartamento.index');
    Route::get('/cerrar-apartamento/create', [CerrarApartamentoController::class, 'create'])->name('admin.cerrar-apartamento.create');
    Route::post('/cerrar-apartamento', [CerrarApartamentoController::class, 'store'])->name('admin.cerrar-apartamento.store');



    // Huespedes
    Route::get('/huespedes', [App\Http\Controllers\HuespedesController::class, 'index'])->name('huespedes.index');
    Route::get('/huespedes/create', [App\Http\Controllers\HuespedesController::class, 'create'])->name('huespedes.create');
    Route::post('/huespedes', [App\Http\Controllers\HuespedesController::class, 'store'])->name('huespedes.store');
    Route::get('/huesped/show/{id}', [App\Http\Controllers\HuespedesController::class, 'show'])->name('huespedes.show');
    Route::get('/huespedes/{id}/edit', [App\Http\Controllers\HuespedesController::class, 'edit'])->name('huespedes.edit');
    Route::put('/huespedes/{id}', [App\Http\Controllers\HuespedesController::class, 'update'])->name('huespedes.update');
    Route::delete('/huespedes/{id}', [App\Http\Controllers\HuespedesController::class, 'destroy'])->name('huespedes.destroy');

    // Configuraciones
    // Ruta legacy - redirige a portal público
    Route::get('/configuracion', [App\Http\Controllers\ConfiguracionesController::class, 'index'])->name('configuracion.index');
    // Las rutas genéricas /{id}/edit y /{id}/update están ahora dentro del grupo configuracion.*

    // Bancos
    Route::get('/bancos', [App\Http\Controllers\BancosController::class, 'index'])->name('admin.bancos.index');
    Route::get('/bancos-create', [App\Http\Controllers\BancosController::class, 'create'])->name('admin.bancos.create');
    Route::post('/bancos/store', [App\Http\Controllers\BancosController::class, 'store'])->name('admin.bancos.store');
    Route::get('/bancos/{banco}/edit', [App\Http\Controllers\BancosController::class, 'edit'])->name('admin.bancos.edit');
    Route::post('/bancos/{banco}/update', [App\Http\Controllers\BancosController::class, 'update'])->name('admin.bancos.update');
    Route::post('/bancos/{banco}/destroy', [App\Http\Controllers\BancosController::class, 'destroy'])->name('admin.bancos.destroy');

    // Edificios
    Route::get('/edificios', [App\Http\Controllers\EdificiosController::class, 'index'])->name('admin.edificios.index');
    Route::get('/edificio-create', [App\Http\Controllers\EdificiosController::class, 'create'])->name('admin.edificio.create');
    Route::post('/edificio/store', [App\Http\Controllers\EdificiosController::class, 'store'])->name('admin.edificio.store');
    Route::get('/edificio/{id}', [App\Http\Controllers\EdificiosController::class, 'show'])->name('admin.edificio.show');
    Route::get('/edificio/{id}/edit', [App\Http\Controllers\EdificiosController::class, 'edit'])->name('admin.edificio.edit');
    Route::post('/edificio/{id}/update', [App\Http\Controllers\EdificiosController::class, 'update'])->name('admin.edificio.update');
    Route::post('/edificio/{id}/destroy', [App\Http\Controllers\EdificiosController::class, 'destroy'])->name('admin.edificio.destroy');

    // Categoria de Gastos
    Route::get('/categoria-gastos', [App\Http\Controllers\CategoriaGastosController::class, 'index'])->name('admin.categoriaGastos.index');
    Route::get('/categoria-gastos/create', [App\Http\Controllers\CategoriaGastosController::class, 'create'])->name('admin.categoriaGastos.create');
    Route::post('/categoria-gastos/store', [App\Http\Controllers\CategoriaGastosController::class, 'store'])->name('admin.categoriaGastos.store');
    Route::get('/categoria-gastos/{categoria}/edit', [App\Http\Controllers\CategoriaGastosController::class, 'edit'])->name('admin.categoriaGastos.edit');
    Route::put('/categoria-gastos/{categoria}/update', [App\Http\Controllers\CategoriaGastosController::class, 'update'])->name('admin.categoriaGastos.update');
    Route::post('/categoria-gastos/{categoria}/destroy', [App\Http\Controllers\CategoriaGastosController::class, 'destroy'])->name('admin.categoriaGastos.destroy');

    // Gastos
    Route::get('/gastos', [App\Http\Controllers\GastosController::class, 'index'])->name('admin.gastos.index');
    Route::get('/gastos/create', [App\Http\Controllers\GastosController::class, 'create'])->name('admin.gastos.create');
    Route::post('/gastos/store', [App\Http\Controllers\GastosController::class, 'store'])->name('admin.gastos.store');
    Route::get('/gastos/{categoria}/edit', [App\Http\Controllers\GastosController::class, 'edit'])->name('admin.gastos.edit');
    Route::post('/gastos/{categoria}/update', [App\Http\Controllers\GastosController::class, 'update'])->name('admin.gastos.update');
    Route::post('/gastos/{id}/destroy', [App\Http\Controllers\GastosController::class, 'destroy'])->name('admin.gastos.destroy');
    Route::get('/gastos/download/{id}', [App\Http\Controllers\GastosController::class, 'download'])->name('gastos.download');

    // Categoria de Ingresos
    Route::get('/categoria-ingresos', [App\Http\Controllers\CategoriaIngresosController::class, 'index'])->name('admin.categoriaIngresos.index');
    Route::get('/categoria-ingresos/create', [App\Http\Controllers\CategoriaIngresosController::class, 'create'])->name('admin.categoriaIngresos.create');
    Route::post('/categoria-ingresos/store', [App\Http\Controllers\CategoriaIngresosController::class, 'store'])->name('admin.categoriaIngresos.store');
    Route::get('/categoria-ingresos/{categoria}/edit', [App\Http\Controllers\CategoriaIngresosController::class, 'edit'])->name('admin.categoriaIngresos.edit');
    Route::put('/categoria-ingresos/{categoria}/update', [App\Http\Controllers\CategoriaIngresosController::class, 'update'])->name('admin.categoriaIngresos.update');
    Route::post('/categoria-ingresos/{categoria}/destroy', [App\Http\Controllers\CategoriaIngresosController::class, 'destroy'])->name('admin.categoriaIngresos.destroy');

    // Estados del Diario de Caja
    Route::get('/estados-diario', [App\Http\Controllers\EstadosDiarioController::class, 'index'])->name('admin.estadosDiario.index');
    Route::get('/estados-diario/create', [App\Http\Controllers\EstadosDiarioController::class, 'create'])->name('admin.estadosDiario.create');
    Route::post('/estados-diario/store', [App\Http\Controllers\EstadosDiarioController::class, 'store'])->name('admin.estadosDiario.store');
    Route::get('/estados-diario/{categoria}/edit', [App\Http\Controllers\EstadosDiarioController::class, 'edit'])->name('admin.estadosDiario.edit');
    Route::post('/estados-diario/{categoria}/update', [App\Http\Controllers\EstadosDiarioController::class, 'update'])->name('admin.estadosDiario.update');
    Route::post('/estados-diario/{categoria}/destroy', [App\Http\Controllers\EstadosDiarioController::class, 'destroy'])->name('admin.estadosDiario.destroy');

     // Ingresos
    Route::get('/ingresos', [App\Http\Controllers\IngresosController::class, 'index'])->name('admin.ingresos.index');
    Route::get('/ingresos/create', [App\Http\Controllers\IngresosController::class, 'create'])->name('admin.ingresos.create');
    Route::post('/ingresos/store', [App\Http\Controllers\IngresosController::class, 'store'])->name('admin.ingresos.store');
    Route::get('/ingresos/{categoria}/edit', [App\Http\Controllers\IngresosController::class, 'edit'])->name('admin.ingresos.edit');
    Route::put('/ingresos/{categoria}/update', [App\Http\Controllers\IngresosController::class, 'update'])->name('admin.ingresos.update');
    Route::post('/ingresos/{id}/destroy', [App\Http\Controllers\IngresosController::class, 'destroy'])->name('admin.ingresos.destroy');
    Route::get('/ingresos/download/{id}', [App\Http\Controllers\IngresosController::class, 'download'])->name('ingresos.download');

    // Dashboard Financiero
    Route::get('/tesoreria/dashboard', [App\Http\Controllers\DashboardFinancieroController::class, 'index'])->name('admin.tesoreria.dashboard');
    Route::post('/admin/tesoreria/factura/{id}/estado', [App\Http\Controllers\DashboardFinancieroController::class, 'cambiarEstado'])->name('admin.tesoreria.cambiarEstado');
    Route::post('/admin/tesoreria/facturas/asignar-referencias', [App\Http\Controllers\DashboardFinancieroController::class, 'asignarReferencias'])->name('admin.tesoreria.asignarReferencias');
    // Configuracion Asesorias
    Route::get('/configuracion/asesorias', [App\Http\Controllers\AsesoriaConfigController::class, 'index'])->name('configuracion.asesorias.index');
    Route::get('/configuracion/asesorias/crear', [App\Http\Controllers\AsesoriaConfigController::class, 'create'])->name('configuracion.asesorias.create');
    Route::post('/configuracion/asesorias', [App\Http\Controllers\AsesoriaConfigController::class, 'store'])->name('configuracion.asesorias.store');
    Route::get('/configuracion/asesorias/{id}/editar', [App\Http\Controllers\AsesoriaConfigController::class, 'edit'])->name('configuracion.asesorias.edit');
    Route::put('/configuracion/asesorias/{id}', [App\Http\Controllers\AsesoriaConfigController::class, 'update'])->name('configuracion.asesorias.update');
    Route::delete('/configuracion/asesorias/{id}', [App\Http\Controllers\AsesoriaConfigController::class, 'destroy'])->name('configuracion.asesorias.destroy');
    Route::post('/configuracion/asesorias/{id}/enviar-ahora', [App\Http\Controllers\AsesoriaConfigController::class, 'enviarAhora'])->name('configuracion.asesorias.enviarAhora');

    // Facturas Recibidas
    Route::get('/facturas-recibidas', [App\Http\Controllers\FacturasRecibidasController::class, 'index'])->name('admin.facturasRecibidas.index');
    Route::post('/facturas-recibidas/{id}/subir', [App\Http\Controllers\FacturasRecibidasController::class, 'subirFactura'])->name('admin.facturasRecibidas.subir');
    Route::get('/facturas-recibidas/{id}/descargar', [App\Http\Controllers\FacturasRecibidasController::class, 'descargarFactura'])->name('admin.facturasRecibidas.descargar');

    // Diario de Caja
    Route::get('/diario-caja', [App\Http\Controllers\DiarioCajaController::class, 'index'])->name('admin.diarioCaja.index');
    Route::get('/diario-caja/ingreso', [App\Http\Controllers\DiarioCajaController::class, 'createIngreso'])->name('admin.diarioCaja.ingreso');
    Route::get('/diario-caja/gasto', [App\Http\Controllers\DiarioCajaController::class, 'createGasto'])->name('admin.diarioCaja.gasto');
    Route::post('/diario-caja/store', [App\Http\Controllers\DiarioCajaController::class, 'store'])->name('admin.diarioCaja.store');
    Route::post('/diario-caja/store/gasto', [App\Http\Controllers\DiarioCajaController::class, 'storeGasto'])->name('admin.diarioCaja.storeGasto');
    Route::get('/diario-caja/{id}/edit', [App\Http\Controllers\DiarioCajaController::class, 'edit'])->name('admin.diarioCaja.edit');
    Route::post('/diario-caja/{id}/update', [App\Http\Controllers\DiarioCajaController::class, 'update'])->name('admin.diarioCaja.update');
    Route::post('/diario-caja/{id}/destroy', [App\Http\Controllers\DiarioCajaController::class, 'destroy'])->name('admin.diarioCaja.destroy');
    Route::post('/diario-caja/{id}/destroy-linea', [App\Http\Controllers\DiarioCajaController::class, 'destroyDiarioCaja'])->name('admin.diarioCaja.destroyDiarioCaja');
    Route::post('/diario-caja/importar-excel-bankinter', [App\Http\Controllers\DiarioCajaController::class, 'importarExcelBankinter'])->name('admin.diarioCaja.importarExcelBankinter');

    // Facturas pendientes (panel admin)
    Route::get('/facturas-pendientes', [App\Http\Controllers\FacturasPendientesController::class, 'index'])->name('admin.facturasPendientes.index');
    Route::get('/facturas-pendientes/{id}/imagen', [App\Http\Controllers\FacturasPendientesController::class, 'imagen'])->name('admin.facturasPendientes.imagen');
    Route::post('/facturas-pendientes/{id}/asociar', [App\Http\Controllers\FacturasPendientesController::class, 'asociarManual'])->name('admin.facturasPendientes.asociar');
    Route::post('/facturas-pendientes/{id}/reintentar', [App\Http\Controllers\FacturasPendientesController::class, 'reintentar'])->name('admin.facturasPendientes.reintentar');
    Route::post('/facturas-pendientes/{id}/descartar', [App\Http\Controllers\FacturasPendientesController::class, 'descartar'])->name('admin.facturasPendientes.descartar');

    // Cuentas Contables
    Route::get('/cuentas-contables', [App\Http\Controllers\CuentasContableController::class, 'index'])->name('admin.cuentasContables.index');
    Route::get('/cuentas-contables/create', [App\Http\Controllers\CuentasContableController::class, 'create'])->name('admin.cuentasContables.create');
    Route::post('/cuentas-contables/store', [App\Http\Controllers\CuentasContableController::class, 'store'])->name('admin.cuentasContables.store');
    Route::get('/cuentas-contables/{id}/edit', [App\Http\Controllers\CuentasContableController::class, 'edit'])->name('admin.cuentasContables.edit');
    Route::post('/cuentas-contables/updated', [App\Http\Controllers\CuentasContableController::class, 'updated'])->name('admin.cuentasContables.updated');
    Route::delete('/cuentas-contables/destroy/{id}', [App\Http\Controllers\CuentasContableController::class, 'destroy'])->name('admin.cuentasContables.destroy');

    Route::get('/cuentas-contables/get-cuentas', [App\Http\Controllers\CuentasContableController::class, 'getCuentasByDataTables'])->name('admin.cuentasContables.getClients');

    // Sub-Cuentas Contables
    Route::get('/sub-cuentas-contables', [App\Http\Controllers\SubCuentasContableController::class, 'index'])->name('admin.subCuentasContables.index');
    Route::get('/sub-cuentas-contables/create', [App\Http\Controllers\SubCuentasContableController::class, 'create'])->name('admin.subCuentasContables.create');
    Route::post('/sub-cuentas-contables/store', [App\Http\Controllers\SubCuentasContableController::class, 'store'])->name('admin.subCuentasContables.store');
    Route::get('/sub-cuentas-contables/{id}/edit', [App\Http\Controllers\SubCuentasContableController::class, 'edit'])->name('admin.subCuentasContables.edit');
    Route::post('/sub-cuentas-contables/updated', [App\Http\Controllers\SubCuentasContableController::class, 'updated'])->name('admin.subCuentasContables.updated');
    Route::delete('/sub-cuentas-contables/destroy/{id}', [App\Http\Controllers\SubCuentasContableController::class, 'destroy'])->name('admin.subCuentasContables.destroy');

    // Sub-Cuentas Hijas Contables
    Route::get('/sub-cuentas-hijas-contables', [App\Http\Controllers\SubCuentasHijoController::class, 'index'])->name('admin.subCuentasHijaContables.index');
    Route::get('/sub-cuentas-hijas-contables/create', [App\Http\Controllers\SubCuentasHijoController::class, 'create'])->name('admin.subCuentasHijaContables.create');
    Route::post('/sub-cuentas-hijas-contables/store', [App\Http\Controllers\SubCuentasHijoController::class, 'store'])->name('admin.subCuentasHijaContables.store');
    Route::get('/sub-cuentas-hijas-contables/{id}/edit', [App\Http\Controllers\SubCuentasHijoController::class, 'edit'])->name('admin.subCuentasHijaContables.edit');
    Route::post('/sub-cuentas-hijas-contables/updated', [App\Http\Controllers\SubCuentasHijoController::class, 'updated'])->name('admin.subCuentasHijaContables.updated');
    Route::delete('/sub-cuentas-hijas-contables/destroy/{id}', [App\Http\Controllers\SubCuentasHijoController::class, 'destroy'])->name('admin.subCuentasHijaContables.destroy');

    // Grupos Contables
    Route::get('/grupo-contable', [App\Http\Controllers\GrupoContabilidadController::class, 'index'])->name('admin.grupoContabilidad.index');
    Route::get('/grupo-contable/create', [App\Http\Controllers\GrupoContabilidadController::class, 'create'])->name('admin.grupoContabilidad.create');
    Route::post('/grupo-contable/store', [App\Http\Controllers\GrupoContabilidadController::class, 'store'])->name('admin.grupoContabilidad.store');
    Route::get('/grupo-contable/{id}/edit', [App\Http\Controllers\GrupoContabilidadController::class, 'edit'])->name('admin.grupoContabilidad.edit');
    Route::post('/grupo-contable/updated', [App\Http\Controllers\GrupoContabilidadController::class, 'updated'])->name('admin.grupoContabilidad.updated');
    Route::delete('/grupo-contable/destroy/{id}', [App\Http\Controllers\GrupoContabilidadController::class, 'destroy'])->name('admin.grupoContabilidad.destroy');

    // Sub-Grupos Contables
    Route::get('/sub-grupo-contable', [App\Http\Controllers\SubGrupoContabilidadController::class, 'index'])->name('admin.subGrupoContabilidad.index');
    Route::get('/sub-grupo-contable/create', [App\Http\Controllers\SubGrupoContabilidadController::class, 'create'])->name('admin.subGrupoContabilidad.create');
    Route::post('/sub-grupo-contable/store', [App\Http\Controllers\SubGrupoContabilidadController::class, 'store'])->name('admin.subGrupoContabilidad.store');
    Route::get('/sub-grupo-contable/{id}/edit', [App\Http\Controllers\SubGrupoContabilidadController::class, 'edit'])->name('admin.subGrupoContabilidad.edit');
    Route::post('/sub-grupo-contable/updated', [App\Http\Controllers\SubGrupoContabilidadController::class, 'updated'])->name('admin.subGrupoContabilidad.updated');
    Route::delete('/sub-grupo-contable/destroy/{id}', [App\Http\Controllers\SubGrupoContabilidadController::class, 'destroy'])->name('admin.subGrupoContabilidad.destroy');

    // Ver usuario
    Route::get('/jornada', [App\Http\Controllers\JornadaController::class, 'index'])->name('admin.jornada.index');

    // Configuraciones - Mantener rutas legacy para compatibilidad
    // Ruta legacy - redirige a portal público
    Route::get('/configuracion', [App\Http\Controllers\ConfiguracionesController::class, 'index'])->name('configuracion.index');
    // Las rutas genéricas /{id}/edit y /{id}/update están ahora dentro del grupo configuracion.* al final
    // Legacy routes - mantener para compatibilidad con código existente
    Route::post('/configuracion/store-reparaciones', [App\Http\Controllers\ConfiguracionesController::class, 'storeReparaciones'])->name('configuracion.storeReparaciones');
    Route::post('/configuracion/update-reparaciones/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'updateReparaciones'])->name('configuracion.updateReparaciones');
    Route::post('/configuracion/delete-reparaciones/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'deleteReparaciones'])->name('configuracion.deleteReparaciones');
    Route::post('/configuracion/update-anio', [App\Http\Controllers\ConfiguracionesController::class, 'updateAnio'])->name('configuracion.updateAnio');
    Route::post('/configuracion/cierre-anio', [App\Http\Controllers\ConfiguracionesController::class, 'cierreAnio'])->name('configuracion.cierreAnio');
    Route::post('/configuracion/store-limpiadora', [App\Http\Controllers\ConfiguracionesController::class, 'storeLimpiadora'])->name('configuracion.storeLimpiadora');
    Route::post('/configuracion/update-limpiadora/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'updateLimpiadora'])->name('configuracion.updateLimpiadora');
    Route::post('/configuracion/delete-limpiadora/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'deleteLimpiadora'])->name('configuracion.deleteLimpiadora');
    Route::post('/configuracion/update-saldo', [App\Http\Controllers\ConfiguracionesController::class, 'saldoInicial'])->name('configuracion.saldoInicial');
    Route::post('/configuracion/update-estado', [App\Http\Controllers\ConfiguracionesController::class, 'updateEstado'])->name('configuracion.updateEstado');
    Route::post('/configuracion/update-metodo-entrada', [App\Http\Controllers\ConfiguracionesController::class, 'updateMetodoEntrada'])->name('configuracion.updateMetodoEntrada');

    // Formas de Pago
    Route::post('/forma-pago/store', [App\Http\Controllers\FormasDePagoController::class, 'store'])->name('formaPago.store');
    Route::post('/forma-pago/update/{id}', [App\Http\Controllers\FormasDePagoController::class, 'update'])->name('formaPago.update');
    Route::post('/forma-pago/delete/{id}', [App\Http\Controllers\FormasDePagoController::class, 'delete'])->name('formaPago.delete');

    // Añadir apartamento para limpieza a fondo
    Route::get('/limpieza-apartamento', [App\Http\Controllers\GestionApartamentoController::class, 'limpiezaFondo'])->name('admin.limpiezaFondo.index');
    Route::get('/limpieza-apartamento/create', [App\Http\Controllers\GestionApartamentoController::class, 'limpiezaCreate'])->name('admin.limpiezaFondo.create');
    Route::post('/limpieza-apartamento', [App\Http\Controllers\GestionApartamentoController::class, 'limpiezaFondoStore'])->name('admin.limpiezaFondo.store');
    Route::get('/limpieza-apartamento/edit/{id}', [App\Http\Controllers\GestionApartamentoController::class, 'limpiezaFondoEdit'])->name('admin.limpiezaFondo.edit');
    Route::post('/limpieza-apartamento/update/{id}', [App\Http\Controllers\GestionApartamentoController::class, 'limpiezaFondoUpdate'])->name('admin.limpiezaFondo.update');
    Route::post('/limpieza-apartamento/destroy/{id}', [App\Http\Controllers\GestionApartamentoController::class, 'limpiezaFondoDestroy'])->name('admin.limpiezaFondo.destroy');


    Route::get('/plan-contable', [App\Http\Controllers\PlanContableController::class, 'index'])->name('admin.planContable.index');
    Route::get('/plan-contable/json', [App\Http\Controllers\PlanContableController::class, 'json']);

    // Legacy routes para compatibilidad (redirigen a nuevas rutas anidadas)
    Route::post('/actualizar-prompt', [App\Http\Controllers\ConfiguracionesController::class, 'actualizarPrompt'])->name('configuracion.actualizarPrompt');
    Route::post('/add-emails', [App\Http\Controllers\ConfiguracionesController::class, 'addEmailNotificaciones'])->name('configuracion.emails.add');
    Route::post('/delete-emails/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'deleteEmailNotificaciones'])->name('configuracion.emails.delete');
    Route::post('/update-emails/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'updateEmailNotificaciones'])->name('configuracion.emails.update');

    // ============================================
    // RUTAS ANIDADAS DE CONFIGURACIÓN (REFACTORIZADAS)
    // ============================================
    Route::prefix('configuracion')->name('configuracion.')->group(function () {
        // IMPORTANTE: Las rutas específicas deben ir ANTES de las genéricas con {id}

        // Sección: SEO y SEM
        Route::prefix('seo')->name('seo.')->group(function () {
            Route::get('/', [App\Http\Controllers\ConfiguracionesController::class, 'seo'])->name('index');
            Route::post('/update', [App\Http\Controllers\ConfiguracionesController::class, 'updateSeo'])->name('update');
        });

        // Sección: MIR Hospedajes (debe ir antes de las rutas genéricas)
        Route::prefix('mir')->name('mir.')->group(function () {
            Route::get('/', [App\Http\Controllers\ConfiguracionesController::class, 'mirHospedajes'])->name('index');
            Route::post('/update', [App\Http\Controllers\ConfiguracionesController::class, 'updateMIR'])->name('update');
        });

        // Sección: Portal Público
        Route::prefix('portal-publico')->name('portal-publico.')->group(function () {
            Route::get('/', [App\Http\Controllers\ConfiguracionesController::class, 'portalPublico'])->name('index');
            Route::post('/', [App\Http\Controllers\ConfiguracionesController::class, 'updatePortalPublico'])->name('update');
        });

        // Sección: Credenciales
        Route::prefix('credenciales')->name('credenciales.')->group(function () {
            Route::get('/', [App\Http\Controllers\ConfiguracionesController::class, 'credenciales'])->name('index');

            // Credenciales Bankinter (CRUD sobre la tabla bankinter_credentials)
            Route::prefix('bankinter')->name('bankinter.')->group(function () {
                Route::post('/', [App\Http\Controllers\ConfiguracionesController::class, 'bankinterStore'])->name('store');
                Route::put('/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'bankinterUpdate'])->name('update');
                Route::delete('/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'bankinterDestroy'])->name('destroy');
                Route::post('/{id}/toggle', [App\Http\Controllers\ConfiguracionesController::class, 'bankinterToggle'])->name('toggle');
            });

            Route::get('/{id}/edit', [App\Http\Controllers\ConfiguracionesController::class, 'edit'])->name('edit');
            Route::put('/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'update'])->name('update');
        });

        // Sección: Contabilidad
        Route::prefix('contabilidad')->name('contabilidad.')->group(function () {
            Route::get('/', [App\Http\Controllers\ConfiguracionesController::class, 'contabilidad'])->name('index');
            Route::post('/saldo-inicial', [App\Http\Controllers\ConfiguracionesController::class, 'saldoInicial'])->name('saldo-inicial');
            Route::post('/update-anio', [App\Http\Controllers\ConfiguracionesController::class, 'updateAnio'])->name('update-anio');
        });

        // Sección: Reparaciones
        Route::prefix('reparaciones')->name('reparaciones.')->group(function () {
            Route::get('/', [App\Http\Controllers\ConfiguracionesController::class, 'reparaciones'])->name('index');
            Route::post('/', [App\Http\Controllers\ConfiguracionesController::class, 'storeReparaciones'])->name('store');
            Route::put('/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'updateReparaciones'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'deleteReparaciones'])->name('destroy');
        });

        // Sección: Limpiadoras
        Route::prefix('limpiadoras')->name('limpiadoras.')->group(function () {
            Route::get('/', [App\Http\Controllers\ConfiguracionesController::class, 'limpiadoras'])->name('index');
            Route::post('/', [App\Http\Controllers\ConfiguracionesController::class, 'storeLimpiadora'])->name('store');
            Route::put('/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'updateLimpiadora'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'deleteLimpiadora'])->name('destroy');
        });

        // Sección: Notificaciones
        Route::prefix('notificaciones')->name('notificaciones.')->group(function () {
            Route::get('/', [App\Http\Controllers\ConfiguracionesController::class, 'notificaciones'])->name('index');
            Route::post('/emails', [App\Http\Controllers\ConfiguracionesController::class, 'addEmailNotificaciones'])->name('emails.add');
            Route::put('/emails/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'updateEmailNotificaciones'])->name('emails.update');
            Route::delete('/emails/{id}', [App\Http\Controllers\ConfiguracionesController::class, 'deleteEmailNotificaciones'])->name('emails.destroy');
        });

        // Sección: Prompt IA
        Route::prefix('prompt-ia')->name('prompt-ia.')->group(function () {
            Route::get('/', [App\Http\Controllers\ConfiguracionesController::class, 'promptIa'])->name('index');
            Route::post('/', [App\Http\Controllers\ConfiguracionesController::class, 'actualizarPrompt'])->name('update');
        });

        // Sección: Plataforma Estado
        Route::prefix('plataforma-estado')->name('plataforma-estado.')->group(function () {
            Route::get('/', [App\Http\Controllers\ConfiguracionesController::class, 'plataformaEstado'])->name('index');
            Route::post('/', [App\Http\Controllers\ConfiguracionesController::class, 'updateEstado'])->name('update');
        });

        // Rutas genéricas legacy (deben ir AL FINAL para no interferir con rutas específicas)
        Route::get('/{id}/edit', [App\Http\Controllers\ConfiguracionesController::class, 'edit'])->name('edit');
        Route::post('/{id}/update', [App\Http\Controllers\ConfiguracionesController::class, 'update'])->name('update');
    });

    // Checklists - Limpieza
    Route::get('/checklists', [App\Http\Controllers\ChecklistController::class, 'index'])->name('admin.checklists.index');
    Route::get('/checklists-create', [App\Http\Controllers\ChecklistController::class, 'create'])->name('admin.checklists.create');
    Route::post('/checklists/store', [App\Http\Controllers\ChecklistController::class, 'store'])->name('admin.checklists.store');
    Route::get('/checklists/{id}', [App\Http\Controllers\ChecklistController::class, 'show'])->name('admin.checklists.show');
    Route::get('/checklists/{id}/edit', [App\Http\Controllers\ChecklistController::class, 'edit'])->name('admin.checklists.edit');
    Route::post('/checklists/{id}/update', [App\Http\Controllers\ChecklistController::class, 'update'])->name('admin.checklists.update');
    Route::delete('/checklists/{id}/destroy', [App\Http\Controllers\ChecklistController::class, 'destroy'])->name('admin.checklists.destroy');
    Route::post('/checklists/{id}/toggle-status', [App\Http\Controllers\ChecklistController::class, 'toggleStatus'])->name('admin.checklists.toggle-status');

    // Items_checklist - Limpieza
    Route::get('/items_checklist', [App\Http\Controllers\ItemChecklistController::class, 'index'])->name('admin.itemsChecklist.index');
    Route::get('/items_checklist-create', [App\Http\Controllers\ItemChecklistController::class, 'create'])->name('admin.itemsChecklist.create');
    Route::post('/items_checklist/store', [App\Http\Controllers\ItemChecklistController::class, 'store'])->name('admin.itemsChecklist.store');
    Route::get('/items_checklist/{id}', [App\Http\Controllers\ItemChecklistController::class, 'show'])->name('admin.itemsChecklist.show');
    Route::get('/items_checklist/{id}/edit', [App\Http\Controllers\ItemChecklistController::class, 'edit'])->name('admin.itemsChecklist.edit');
    Route::post('/items_checklist/{id}/update', [App\Http\Controllers\ItemChecklistController::class, 'update'])->name('admin.itemsChecklist.update');
    Route::delete('/items_checklist/{id}', [App\Http\Controllers\ItemChecklistController::class, 'destroy'])->name('admin.itemsChecklist.destroy');
    Route::post('/items_checklist/{id}/toggle-status', [App\Http\Controllers\ItemChecklistController::class, 'toggleStatus'])->name('admin.itemsChecklist.toggle-status');
    Route::post('/items_checklist/reorder', [App\Http\Controllers\ItemChecklistController::class, 'reorder'])->name('admin.itemsChecklist.reorder');

    // Proveedores
    Route::get('/proveedores', [App\Http\Controllers\ProveedoresController::class, 'index'])->name('admin.proveedores.index');
    Route::get('/proveedores/create', [App\Http\Controllers\ProveedoresController::class, 'create'])->name('admin.proveedores.create');
    Route::post('/proveedores/store', [App\Http\Controllers\ProveedoresController::class, 'store'])->name('admin.proveedores.store');
    Route::get('/proveedores/{id}/edit', [App\Http\Controllers\ProveedoresController::class, 'edit'])->name('admin.proveedores.edit');
    Route::post('/proveedores/{id}/update', [App\Http\Controllers\ProveedoresController::class, 'update'])->name('admin.proveedores.update');
    Route::post('/proveedores/{id}/destroy', [App\Http\Controllers\ProveedoresController::class, 'destroy'])->name('admin.proveedores.destroy');

    // Tabla de Reservas
    Route::get('/tabla-reservas', [App\Http\Controllers\TablaReservasController::class, 'index'])->name('admin.tablaReservas.index');
    Route::get('/get-reservas', [App\Http\Controllers\ReservasController::class, 'getReservas'])->name('reservas.get');


    // Facturas
    Route::get('/facturas',[App\Http\Controllers\InvoicesController::class, 'index'])->name('admin.facturas.index');
    Route::get('/facturas/{id}/edit',[App\Http\Controllers\InvoicesController::class, 'edit'])->name('admin.facturas.edit');
    Route::put('/facturas/{id}',[App\Http\Controllers\InvoicesController::class, 'update'])->name('admin.facturas.update');
    // [2026-04-17] Generar token de descarga y enviar la factura al cliente (WhatsApp + email)
    Route::post('/facturas/{id}/enviar-cliente',[App\Http\Controllers\InvoicesController::class, 'enviarAlCliente'])->name('admin.facturas.enviarCliente');
    Route::get('/facturas-excel',[App\Http\Controllers\InvoicesController::class, 'exportInvoices'])->name('admin.facturas.export');
    Route::get('/facturas-descargar/{id}',[App\Http\Controllers\InvoicesController::class, 'previewPDF'])->name('admin.facturas.previewPDF');
    Route::get('/invoice/pdf/{id}', [App\Http\Controllers\InvoicesController::class, 'generateInvoicePDF'])->name('admin.facturas.generatePdf');
    Route::post('/generar-factura',[App\Http\Controllers\InvoicesController::class, 'facturar'])->name('admin.facturas.facturar');
    Route::post('/facturas/update-fecha/{id}', [App\Http\Controllers\InvoicesController::class, 'updateFecha'])->name('admin.facturas.updateFecha');
    Route::post('/facturas/{id}/update-fecha-referencia', [App\Http\Controllers\InvoicesController::class, 'updateFechaYRecalcularReferencia'])->name('admin.facturas.updateFechaReferencia');
    Route::post('/facturas/{id}/recalcular', [App\Http\Controllers\InvoicesController::class, 'recalculateFromReserva'])->name('admin.facturas.recalculate');
    Route::get('/admin/facturas/download-zip', [App\Http\Controllers\InvoicesController::class, 'downloadInvoicesZip'])->name('admin.facturas.downloadZip');

    // Facturas Rectificativas
    Route::get('/facturas/{id}/rectificar', [App\Http\Controllers\InvoicesController::class, 'createRectificativa'])->name('admin.facturas.createRectificativa');
    Route::post('/facturas/{id}/rectificar', [App\Http\Controllers\InvoicesController::class, 'storeRectificativa'])->name('admin.facturas.storeRectificativa');
    Route::get('/facturas/{id}/rectificativas', [App\Http\Controllers\InvoicesController::class, 'showRectificativas'])->name('admin.facturas.showRectificativas');


    // Vacaciones
    Route::get('/holiday/index', [AdminHolidaysController::class, 'index'])->name('holiday.admin.index');
    Route::get('/holiday/admin-create', [AdminHolidaysController::class, 'create'])->name('holiday.admin.create');
    Route::get('/holiday/store', [AdminHolidaysController::class, 'store'])->name('holiday.admin.store');
    Route::get('/holiday/destroy', [AdminHolidaysController::class, 'destroy'])->name('holiday.admin.destroy');
    Route::get('/holidays/admin-edit/{id}', [AdminHolidaysController::class, 'edit'])->name('holiday.admin.edit');
    Route::post('/holidays/admin-update', [AdminHolidaysController::class, 'update'])->name('holiday.admin.update');
    Route::get('/holidays/petitions', [AdminHolidaysController::class, 'usersPetitions'])->name('holiday.admin.petitions');
    Route::get('/holidays/record', [AdminHolidaysController::class, 'addedRecord'])->name('holiday.admin.record');
    Route::get('/holidays/history', [AdminHolidaysController::class, 'allHistory'])->name('holiday.admin.history');
    Route::get('/holidays/managePetition/{id}', [AdminHolidaysController::class, 'managePetition'])->name('holiday.admin.managePetition');
    Route::post('/holidays/acceptHolidays', [AdminHolidaysController::class, 'acceptHolidays'])->name('holiday.admin.acceptHolidays');
    Route::post('/holidays/denyHolidays', [AdminHolidaysController::class, 'denyHolidays'])->name('holiday.admin.denyHolidays');
    Route::post('/holidays/getDate/{holidaysPetitions}', [AdminHolidaysController::class, 'getDate'])->name('holiday.admin.getDate');

    // Estadisticas
    Route::get('/estadisticas',[App\Http\Controllers\InvoicesController::class, 'index'])->name('admin.estadisticas.buscar');



    // Categoria de Emails
    Route::get('/category-email', [App\Http\Controllers\CategoryEmailController::class, 'index'])->name('admin.categoriaEmail.index');
    Route::get('/category-email/create', [App\Http\Controllers\CategoryEmailController::class, 'create'])->name('admin.categoriaEmail.create');
    Route::post('/category-email/store', [App\Http\Controllers\CategoryEmailController::class, 'store'])->name('admin.categoriaEmail.store');
    Route::get('/category-email/{id}/edit', [App\Http\Controllers\CategoryEmailController::class, 'edit'])->name('admin.categoriaEmail.edit');
    Route::post('/category-email/{id}/update', [App\Http\Controllers\CategoryEmailController::class, 'update'])->name('admin.categoriaEmail.update');
    Route::post('/category-email/{id}/destroy', [App\Http\Controllers\CategoryEmailController::class, 'destroy'])->name('admin.categoriaEmail.destroy');

    // Estados de Emails
    Route::get('/status-mail', [App\Http\Controllers\StatusMailController::class, 'index'])->name('admin.statusMail.index');
    Route::get('/status-mail/create', [App\Http\Controllers\StatusMailController::class, 'create'])->name('admin.statusMail.create');
    Route::post('/status-mail/store', [App\Http\Controllers\StatusMailController::class, 'store'])->name('admin.statusMail.store');
    Route::get('/status-mail/{id}/edit', [App\Http\Controllers\StatusMailController::class, 'edit'])->name('admin.statusMail.edit');
    Route::post('/status-mail/{id}/update', [App\Http\Controllers\StatusMailController::class, 'update'])->name('admin.statusMail.update');
    Route::post('/status-mail/{id}/destroy', [App\Http\Controllers\StatusMailController::class, 'destroy'])->name('admin.statusMail.destroy');
    //Route::resource('category_email', CategoryEmailController::class);

    // Usuarios - Empleados
    Route::get('/empleados', [App\Http\Controllers\UserController::class, 'index'])->name('admin.empleados.index');
    Route::get('/empleados/create', [App\Http\Controllers\UserController::class, 'create'])->name('admin.empleados.create');
    Route::post('/empleados/store', [App\Http\Controllers\UserController::class, 'store'])->name('admin.empleados.store');
    Route::get('/empleados/{id}', [App\Http\Controllers\UserController::class, 'show'])->name('admin.empleados.show');
    Route::get('/empleados/{id}/edit', [App\Http\Controllers\UserController::class, 'edit'])->name('admin.empleados.edit');
    Route::put('/empleados/{id}', [App\Http\Controllers\UserController::class, 'update'])->name('admin.empleados.update');
    Route::post('/empleados/{id}/destroy', [App\Http\Controllers\UserController::class, 'destroy'])->name('admin.empleados.destroy');
    Route::post('/empleados/{id}/toggle-status', [App\Http\Controllers\UserController::class, 'toggleStatus'])->name('admin.empleados.toggle-status');
    Route::post('/empleados/{id}/reset-password', [App\Http\Controllers\UserController::class, 'resetPassword'])->name('admin.empleados.reset-password');
    Route::post('/empleados/bulk-action', [App\Http\Controllers\UserController::class, 'bulkAction'])->name('admin.empleados.bulk-action');

    // Emails
    Route::get('/emails', [EmailController::class, 'index'])->name('admin.emails.index');
    Route::get('/emails/{email}', [EmailController::class, 'show'])->name('admin.emails.show');

    Route::get('/emails-recive',[EmailController::class, 'email'])->name('admin.facturas.email');

    // Endpoint AJAX para crear cliente rapido desde el modal del formulario
// de presupuestos (antes del prefijo del grupo para que no herede el path)
Route::post('/presupuestos/cliente-rapido', [App\Http\Controllers\PresupuestoController::class, 'storeClienteRapido'])
    ->name('presupuestos.clienteRapido');

Route::prefix('presupuestos')->group(function () {
        Route::get('/', [PresupuestoController::class, 'index'])->name('presupuestos.index');
        Route::get('/create', [PresupuestoController::class, 'create'])->name('presupuestos.create');
        Route::post('/', [PresupuestoController::class, 'store'])->name('presupuestos.store');
        Route::get('/{id}', [PresupuestoController::class, 'show'])->name('presupuestos.show');
        Route::get('/{id}/edit', [PresupuestoController::class, 'edit'])->name('presupuestos.edit');
        Route::put('/{id}', [PresupuestoController::class, 'update'])->name('presupuestos.update');
        Route::delete('/{id}', [PresupuestoController::class, 'destroy'])->name('presupuestos.destroy');
    });
    Route::post('presupuestos/{presupuesto}/facturar', [PresupuestoController::class, 'facturar'])
    ->name('presupuestos.facturar');

    // admin.facturas.export
});

// Rutas de usuarios logueados
Route::middleware('auth')->group(function () {
    Route::get('/dashboard',[App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/pisos',[App\Http\Controllers\ApartamentosController::class, 'index'])->name('apartamentos.index');


    Route::get('/reservas-calendar', [App\Http\Controllers\ReservasController::class, 'calendar'])->name('reservas.calendar');

    Route::get('/fichajes/estado', [App\Http\Controllers\FichajeController::class, 'estado'])->name('fichajes.estado');
    Route::post('/fichajes/iniciar', [App\Http\Controllers\FichajeController::class, 'iniciarJornada'])->name('fichajes.iniciar');
    Route::post('/fichajes/pausa/iniciar', [App\Http\Controllers\FichajeController::class, 'iniciarPausa'])->name('fichajes.pausa.iniciar');
    Route::post('/fichajes/pausa/finalizar', [App\Http\Controllers\FichajeController::class, 'finalizarPausa'])->name('fichajes.pausa.finalizar');
    Route::post('/fichajes/finalizar', [App\Http\Controllers\FichajeController::class, 'finalizarJornada'])->name('fichajes.finalizar');

    //Holidays(Vacaciones users)
    Route::get('/holidays', [HolidayController::class, 'index'])->name('holiday.index');
    Route::get('/holidays/edit/{id}', [HolidayController::class, 'edit'])->name('holiday.edit');
    Route::post('/holidays/store', [HolidayController::class, 'store'])->name('holiday.store');
    Route::get('/holidays/create', [HolidayController::class, 'create'])->name('holiday.create');

    // Gestion del Apartamento
    Route::get('/gestion', [App\Http\Controllers\GestionApartamentoController::class, 'index'])->name('gestion.index');
    Route::get('/gestion/estadisticas', [App\Http\Controllers\GestionApartamentoController::class, 'estadisticas'])->name('gestion.estadisticas');
    Route::get('/gestion/reserva/{id}/info', [App\Http\Controllers\GestionApartamentoController::class, 'mostrarInfoReserva'])->name('gestion.reserva.info');
    Route::get('/gestion-create/{id}', [App\Http\Controllers\GestionApartamentoController::class, 'create'])->name('gestion.create');
    Route::post('/gestion-store', [App\Http\Controllers\GestionApartamentoController::class, 'store'])->name('gestion.store');
    Route::get('/gestion-edit/{apartamentoLimpieza}', [App\Http\Controllers\GestionApartamentoController::class, 'edit'])->name('gestion.edit');
    Route::post('/gestion-update/{apartamentoLimpieza}', [App\Http\Controllers\GestionApartamentoController::class, 'update'])->name('gestion.update');
    Route::post('/gestion-update-zona-comun/{apartamentoLimpieza}', [App\Http\Controllers\GestionApartamentoController::class, 'updateZonaComun'])->name('gestion.updateZonaComun');
    Route::post('/gestion-finalizar/{apartamentoLimpieza}', [App\Http\Controllers\GestionApartamentoController::class, 'finalizar'])->name('gestion.finalizar');
    Route::post('/gestion-finalizar-zona-comun/{apartamentoLimpieza}', [App\Http\Controllers\GestionApartamentoController::class, 'finalizarZonaComun'])->name('gestion.finalizarZonaComun');
    Route::get('/gestion-edit/{apartamentoLimpieza}/checklist-status', [App\Http\Controllers\GestionApartamentoController::class, 'checklistStatus'])->name('gestion.checklistStatus');
    Route::post('/gestion-store-column', [App\Http\Controllers\GestionApartamentoController::class, 'storeColumn'])->name('gestion.storeColumn');
    // Ruta fija ANTES que las con parámetros para que POST /gestion/update-checkbox no sea capturada por /gestion/{id}/upload-photo
    Route::post('/gestion/update-checkbox', [GestionApartamentoController::class, 'updateCheckbox'])->name('gestion.updateCheckbox');
    Route::post('/gestion/{id}/upload-photo', [GestionApartamentoController::class, 'uploadPhoto'])->name('photo.upload');
    Route::get('/gestion-create-fondo/{id}', [App\Http\Controllers\GestionApartamentoController::class, 'create_fondo'])->name('gestion.create_fondo');
    Route::get('/gestion-edit-zona-comun/{id}', [App\Http\Controllers\GestionApartamentoController::class, 'editZonaComun'])->name('gestion.editZonaComun');
    Route::get('/gestion-create-zona-comun/{id}', [App\Http\Controllers\GestionApartamentoController::class, 'createZonaComun'])->name('gestion.createZonaComun');
    Route::get('/gestion-checklist-status-zona-comun/{apartamentoLimpieza}', [App\Http\Controllers\GestionApartamentoController::class, 'checklistStatusZonaComun'])->name('gestion.checklistStatusZonaComun');

    // Rutas de Incidencias para Limpiadoras
    Route::get('gestion/incidencias', [GestionIncidenciasController::class, 'index'])->name('gestion.incidencias.index');
    Route::get('gestion/incidencias/create', [GestionIncidenciasController::class, 'create'])->name('gestion.incidencias.create');
    Route::post('gestion/incidencias/store', [GestionIncidenciasController::class, 'store'])->name('gestion.incidencias.store');
    Route::get('gestion/incidencias/{id}', [GestionIncidenciasController::class, 'show'])->name('gestion.incidencias.show');
    Route::get('gestion/incidencias/{id}/edit', [GestionIncidenciasController::class, 'edit'])->name('gestion.incidencias.edit');
    Route::post('gestion/incidencias/{id}/update', [GestionIncidenciasController::class, 'update'])->name('gestion.incidencias.update');
    Route::post('gestion/incidencias/{id}/add-photos', [GestionIncidenciasController::class, 'addPhotos'])->name('gestion.incidencias.add-photos');
    Route::post('gestion/incidencias/{id}/destroy', [GestionIncidenciasController::class, 'destroy'])->name('gestion.incidencias.destroy');

    // Rutas de gestión de reservas
    Route::get('gestion/reservas', [App\Http\Controllers\GestionReservasController::class, 'index'])->name('gestion.reservas.index');
    Route::get('gestion/reservas/buscar', [App\Http\Controllers\GestionReservasController::class, 'buscar'])->name('gestion.reservas.buscar');
    Route::get('gestion/reservas/apartamentos', [App\Http\Controllers\GestionReservasController::class, 'obtenerApartamentos'])->name('gestion.reservas.apartamentos');
    Route::get('gestion/reservas/estadisticas', [App\Http\Controllers\GestionReservasController::class, 'estadisticas'])->name('gestion.reservas.estadisticas');
    Route::get('gestion/reservas/{id}', [App\Http\Controllers\GestionReservasController::class, 'show'])->name('gestion.reservas.show');

    // Quick photo upload for fast finalization flow
    Route::post('gestion/limpieza/{id}/foto-rapida', [App\Http\Controllers\GestionApartamentoController::class, 'fotoRapida'])->name('gestion.limpieza.fotoRapida');

    // Rutas de acciones de limpieza (reponer stock y reportar averías)
    Route::post('gestion/limpieza/reponer-stock', [App\Http\Controllers\LimpiezaAccionesController::class, 'reponerStock'])->name('gestion.limpieza.reponer-stock');
    Route::post('gestion/limpieza/reportar-averia', [App\Http\Controllers\LimpiezaAccionesController::class, 'reportarAveria'])->name('gestion.limpieza.reportar-averia');
    Route::get('gestion/limpieza/item-info', [App\Http\Controllers\LimpiezaAccionesController::class, 'getItemInfo'])->name('gestion.limpieza.item-info');

    // Rutas de gestión de turnos y tareas
    Route::resource('gestion/turnos', App\Http\Controllers\Admin\TurnosTrabajoController::class)->names('gestion.turnos');
    Route::post('gestion/turnos/generar', [App\Http\Controllers\Admin\TurnosTrabajoController::class, 'generarTurnos'])->name('gestion.turnos.generar');
    Route::post('gestion/turnos/{turno}/iniciar', [App\Http\Controllers\Admin\TurnosTrabajoController::class, 'iniciarTurno'])->name('gestion.turnos.iniciar');
    Route::post('gestion/turnos/{turno}/finalizar', [App\Http\Controllers\Admin\TurnosTrabajoController::class, 'finalizarTurno'])->name('gestion.turnos.finalizar');
    Route::get('gestion/turnos/estadisticas', [App\Http\Controllers\Admin\TurnosTrabajoController::class, 'estadisticas'])->name('gestion.turnos.estadisticas');

    // Ruta para que las limpiadoras vean sus turnos
    Route::get('gestion/mis-turnos', [App\Http\Controllers\LimpiadoraTurnosController::class, 'index'])->name('gestion.mis-turnos');

    // Rutas para gestión de tareas asignadas
    Route::get('gestion/tareas/{tarea}/info', [App\Http\Controllers\GestionApartamentoController::class, 'infoTarea'])->name('gestion.tareas.info');
    Route::post('gestion/tareas/{tarea}/iniciar', [App\Http\Controllers\GestionApartamentoController::class, 'iniciarTarea'])->name('gestion.tareas.iniciar');
    Route::post('gestion/tareas/{tarea}/finalizar', [App\Http\Controllers\GestionApartamentoController::class, 'finalizarTarea'])->name('gestion.tareas.finalizar');
    Route::post('gestion/tareas/{tarea}/update', [App\Http\Controllers\GestionApartamentoController::class, 'updateTarea'])->name('gestion.updateTarea');
    Route::post('gestion/tareas/{tarea}/checklist/update', [App\Http\Controllers\GestionApartamentoController::class, 'updateChecklistTarea'])->name('gestion.tareas.checklist.update');
    Route::post('gestion/tareas/{tarea}/checklist/finalizar', [App\Http\Controllers\GestionApartamentoController::class, 'finalizarChecklistTarea'])->name('gestion.tareas.checklist.finalizar');
    Route::post('gestion/{apartamentoLimpieza}/articulo-descuento', [App\Http\Controllers\GestionApartamentoController::class, 'registrarDescuentoArticulo'])->name('gestion.articulo-descuento');
    // Rutas para gestión de tareas asignadas
    Route::get('gestion/tareas/{tarea}/checklist', [App\Http\Controllers\GestionApartamentoController::class, 'checklistTarea'])->name('gestion.tareas.checklist');
});

// Rutas de administración para turnos y tareas
Route::middleware(['auth', 'role:ADMIN'])->group(function () {
    // Tipos de tareas
    Route::resource('admin/tipos-tareas', App\Http\Controllers\Admin\TiposTareasController::class)->names('admin.tipos-tareas');
    Route::post('admin/tipos-tareas/{tiposTarea}/toggle-active', [App\Http\Controllers\Admin\TiposTareasController::class, 'toggleActive'])->name('admin.tipos-tareas.toggle-active');
    Route::post('admin/tipos-tareas/{tiposTarea}/duplicar', [App\Http\Controllers\Admin\TiposTareasController::class, 'duplicar'])->name('admin.tipos-tareas.duplicar');

    // Horas extras
    Route::resource('admin/horas-extras', App\Http\Controllers\Admin\HorasExtrasController::class)->names('admin.horas-extras');
    Route::post('admin/horas-extras/{horasExtras}/aprobar', [App\Http\Controllers\Admin\HorasExtrasController::class, 'aprobar'])->name('admin.horas-extras.aprobar');
    Route::post('admin/horas-extras/{horasExtras}/rechazar', [App\Http\Controllers\Admin\HorasExtrasController::class, 'rechazar'])->name('admin.horas-extras.rechazar');
    Route::post('admin/horas-extras/aprobar-multiples', [App\Http\Controllers\Admin\HorasExtrasController::class, 'aprobarMultiples'])->name('admin.horas-extras.aprobar-multiples');
    Route::post('admin/horas-extras/rechazar-multiples', [App\Http\Controllers\Admin\HorasExtrasController::class, 'rechazarMultiples'])->name('admin.horas-extras.rechazar-multiples');
    Route::get('admin/horas-extras/exportar', [App\Http\Controllers\Admin\HorasExtrasController::class, 'exportar'])->name('admin.horas-extras.exportar');
    Route::get('admin/horas-extras/estadisticas', [App\Http\Controllers\Admin\HorasExtrasController::class, 'estadisticas'])->name('admin.horas-extras.estadisticas');

    // Horarios de empleadas
    Route::resource('admin/empleada-horarios', App\Http\Controllers\Admin\EmpleadaHorariosController::class)->names('admin.empleada-horarios');
    Route::post('admin/empleada-horarios/{empleadaHorario}/toggle-active', [App\Http\Controllers\Admin\EmpleadaHorariosController::class, 'toggleActive'])->name('admin.empleada-horarios.toggle-active');
    Route::get('admin/empleada-horarios/empleadas-sin-horario', [App\Http\Controllers\Admin\EmpleadaHorariosController::class, 'empleadasSinHorario'])->name('admin.empleada-horarios.empleadas-sin-horario');

    // Gestión de tareas en turnos
    Route::post('admin/turnos/tareas', [App\Http\Controllers\Admin\TurnosTrabajoController::class, 'addTask'])->name('admin.turnos.tareas.store');
    Route::get('admin/turnos/tareas/{tarea}', [App\Http\Controllers\Admin\TurnosTrabajoController::class, 'showTask'])->name('admin.turnos.tareas.show');
    Route::get('admin/turnos/tareas/{tarea}/edit', [App\Http\Controllers\Admin\TurnosTrabajoController::class, 'editTask'])->name('admin.turnos.tareas.edit');
    Route::put('admin/turnos/tareas/{tarea}', [App\Http\Controllers\Admin\TurnosTrabajoController::class, 'updateTask'])->name('admin.turnos.tareas.update');
    Route::post('admin/turnos/tareas/{tarea}/delete', [App\Http\Controllers\Admin\TurnosTrabajoController::class, 'deleteTask'])->name('admin.turnos.tareas.destroy');
    Route::post('admin/turnos/tareas/{tarea}/toggle', [App\Http\Controllers\Admin\TurnosTrabajoController::class, 'toggleTask'])->name('admin.turnos.tareas.toggle');
    Route::post('admin/turnos/{turno}/reordenar-tareas', [App\Http\Controllers\Admin\TurnosTrabajoController::class, 'reordenarTareas'])->name('admin.turnos.reordenar-tareas');
    Route::post('admin/empleada-horarios/crear-horario-rapido', [App\Http\Controllers\Admin\EmpleadaHorariosController::class, 'crearHorarioRapido'])->name('admin.empleada-horarios.crear-horario-rapido');

    // Admin: Panel Drag & Drop de Turnos
    Route::get('admin/turnos-panel', [App\Http\Controllers\Admin\TurnosAdminController::class, 'index'])->name('admin.turnos-panel.index');
    Route::post('admin/turnos-panel/agregar-tarea', [App\Http\Controllers\Admin\TurnosAdminController::class, 'agregarTarea'])->name('admin.turnos-panel.agregarTarea');
    Route::post('admin/turnos-panel/quitar-tarea/{id}', [App\Http\Controllers\Admin\TurnosAdminController::class, 'quitarTarea'])->name('admin.turnos-panel.quitarTarea');
    Route::post('admin/turnos-panel/mover-tarea', [App\Http\Controllers\Admin\TurnosAdminController::class, 'moverTarea'])->name('admin.turnos-panel.moverTarea');
    Route::post('admin/turnos-panel/regenerar', [App\Http\Controllers\Admin\TurnosAdminController::class, 'regenerar'])->name('admin.turnos-panel.regenerar');

    // Rutas para gestión de días libres por semana
    Route::get('admin/empleada-horarios/{empleadaHorario}/dias-libres', [App\Http\Controllers\Admin\EmpleadaDiasLibresController::class, 'index'])->name('admin.empleada-dias-libres.index');
    Route::get('admin/empleada-horarios/{empleadaHorario}/dias-libres/create', [App\Http\Controllers\Admin\EmpleadaDiasLibresController::class, 'create'])->name('admin.empleada-dias-libres.create');
    Route::post('admin/empleada-horarios/{empleadaHorario}/dias-libres', [App\Http\Controllers\Admin\EmpleadaDiasLibresController::class, 'store'])->name('admin.empleada-dias-libres.store');
    Route::delete('admin/empleada-horarios/{empleadaHorario}/dias-libres/{semanaInicio}', [App\Http\Controllers\Admin\EmpleadaDiasLibresController::class, 'destroy'])->name('admin.empleada-dias-libres.destroy');

});



// Vistas - Solo ADMIN y USER pueden acceder
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home')->middleware('role:ADMIN,USER');
Route::get('/email', [App\Http\Controllers\EstadoController::class, 'index'])->name('email.index');
Route::post('/comprobacion-server', [App\Http\Controllers\EstadoController::class, 'comprobacionServer'])->name('comprobacionServer');





// Añadir Reserva
Route::post('/agregar-reserva', [App\Http\Controllers\ReservasController::class, 'agregarReserva'])->name('reservas.agregarReserva');
Route::post('/reserva/agregar', [App\Http\Controllers\ReservasController::class, 'agregarReserva'])->name('reserva.agregar');

// Verificar Reserva de Booking
Route::get('/verificar-reserva/{reserva}', [App\Http\Controllers\ComprobarReserva::class, 'verificarReserva'])->name('reservas.verificarReserva');
Route::post('/enviar-dni/{id}', [App\Http\Controllers\ReservasController::class, 'enviarDni'])->name('reservas.enviarDni');
Route::post('/cancelar-booking/{reserva}', [App\Http\Controllers\ReservasController::class, 'cancelarBooking'])->name('cancelarBooking.index');
Route::post('/actualizar-booking/{reserva}', [App\Http\Controllers\ReservasController::class, 'actualizarBooking'])->name('actualizarBooking.index');
Route::post('/obtener-reserva', [App\Http\Controllers\ComprobarReserva::class, 'obtenerReserva'])->name('reservas.obtenerReserva');
Route::post('/obtener-codigos', [App\Http\Controllers\ComprobarReserva::class, 'obtenerCodigos'])->name('reservas.obtenerCodigos');
Route::post('/obtener-codigos-airbnb', [App\Http\Controllers\ComprobarReserva::class, 'obtenerCodigosAirBnb'])->name('reservas.obtenerCodigosAirBnb');

// Verificar Reserva de Airbnb
Route::get('/comprobar-reserva/{id}', [App\Http\Controllers\ComprobarReserva::class, 'index'])->name('comprobar.index');
Route::get('/comprobar-reserva-web/{id}', [App\Http\Controllers\ComprobarReserva::class, 'comprobarReservaWeb'])->name('comprobar.comprobarReservaWeb');
Route::post('/cancelar-airbnb/{reserva}', [App\Http\Controllers\ReservasController::class, 'cancelarAirBnb'])->name('cancelarAirBnb.index');
Route::post('/actualizar-airbnb/{reserva}', [App\Http\Controllers\ReservasController::class, 'actualizarAirbnb'])->name('actualizarAirbnb.index');




//Route::resource('gestion/incidencias', GestionIncidenciasController::class, 'index')->names('gestion.incidencias');

// Fotos
Route::get('/fotos-dormitorio/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'index'])->name('fotos.dormitorio');
Route::post('/dormitorio-store/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'store'])->name('fotos.dormitorio-store');
Route::post('/actualizar-fotos-dormitorio/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'actualizar'])->name('actualizar.fotos.dormitorio');

// Route::post('/fotos-dormitorio-store/{id}', [App\Http\Controllers\PhotoController::class, 'dormitorioStore'])->name('fotos.dormitorioStore');
Route::get('/fotos-salon/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'index'])->name('fotos.salon');
Route::post('/fotos-salon-store/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'store'])->name('fotos.salon-store');
Route::post('/actualizar-fotos-salin/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'actualizar'])->name('actualizar.fotos.salon');


Route::get('/fotos-cocina/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'index'])->name('fotos.cocina');
Route::post('/fotos-cocina-store/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'store'])->name('fotos.cocina-store');
Route::post('/actualizar-fotos-cocina/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'actualizar'])->name('actualizar.fotos.cocina');

Route::get('/fotos-cocina-comun/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'index'])->name('fotos.cocina_comun');
Route::post('/fotos-cocina-comun-store/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'store'])->name('fotos.cocina_comun-store');
Route::post('/actualizar-fotos-cocina-comun/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'actualizar'])->name('actualizar.fotos.cocinaComun');

Route::get('/fotos-cajon_de_cama/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'index'])->name('fotos.cajon_de_cama');
Route::post('/fotos-cajon_de_cama/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'store'])->name('fotos.cajon_de_cama-store');
Route::post('/actualizar-fotos-cajon_de_cama/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'actualizar'])->name('actualizar.fotos.cajonDeCama');

Route::get('/fotos-banio/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'index'])->name('fotos.bano');
Route::post('/fotos-banio-store/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'store'])->name('fotos.bano-store');
Route::post('/actualizar-fotos-banio/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'actualizar'])->name('actualizar.fotos.bano');

// Ruta genérica para fotos de checklists
Route::get('/fotos-checklist/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'index'])->name('fotos.checklist');
Route::post('/fotos-checklist-store/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'store'])->name('fotos.checklist-store');
Route::post('/actualizar-fotos-checklist/{id}/{cat}', [App\Http\Controllers\PhotoController::class, 'actualizar'])->name('actualizar.fotos.checklist');

// Rutas para reposición de artículos
Route::post('/reposicion-articulo', [App\Http\Controllers\ReposicionArticuloController::class, 'store'])->name('reposicion.store');
Route::get('/reposiciones/{apartamentoLimpiezaId}', [App\Http\Controllers\ReposicionArticuloController::class, 'getReposiciones'])->name('reposicion.get');


// Obtener DNI
Route::get('/dni-user/{token}', function ($token) {
    return redirect("/dni-scanner/{$token}", 301);
})->name('dni.index');
Route::post('/dni/cambiar-idioma', [App\Http\Controllers\DNIController::class, 'cambiarIdioma'])->name('dni.cambiarIdioma');

Route::post('/guardar-numero-personas', [App\Http\Controllers\DNIController::class, 'storeNumeroPersonas'])->name('dni.storeNumeroPersonas');
Route::post('/dni-user/store', [App\Http\Controllers\DNIController::class, 'store'])->name('dni.store')->middleware('validate.file.upload');
Route::get('/dni-user-subir/{id}', [App\Http\Controllers\DNIController::class, 'dniUpload'])->name('dni.dniUpload');
Route::get('/pasaporte-user-subir/{id}', [App\Http\Controllers\DNIController::class, 'pasaporteUpload'])->name('dni.dniUpload');
Route::get('/dni/{token}', [App\Http\Controllers\DNIController::class, 'dni'])->name('dni.dni');
Route::get('/pasaporte/{token}', [App\Http\Controllers\DNIController::class, 'pasaporte'])->name('dni.pasaporte');

// Nuevo check-in público (formulario simplificado v2.0)
Route::get('/dni-scanner/{token}', [App\Http\Controllers\CheckInPublicController::class, 'index'])->name('checkin.public.index');
Route::post('/checkin/{token}/process', [App\Http\Controllers\CheckInPublicController::class, 'processImages'])->name('checkin.public.process');
Route::get('/checkin/{token}/form', [App\Http\Controllers\CheckInPublicController::class, 'form'])->name('checkin.public.form');
Route::post('/checkin/{token}/store', [App\Http\Controllers\CheckInPublicController::class, 'store'])->name('checkin.public.store');
Route::get('/checkin/{token}/success', [App\Http\Controllers\CheckInPublicController::class, 'success'])->name('checkin.public.success');
Route::get('/checkin/{token}/lang/{locale}', [App\Http\Controllers\CheckInPublicController::class, 'changeLocale'])->name('checkin.public.locale');

// Sistema anterior de escaneo de DNI con cámara (legacy, accesible para admin)
Route::get('/dni-scanner-legacy/{token}', [App\Http\Controllers\DNIScannerController::class, 'index'])->name('dni.scanner.index');
Route::get('/dni-scanner-legacy/{token}/camera', [App\Http\Controllers\DNIScannerController::class, 'showScanner'])->name('dni.scanner.show');
Route::get('/dni-scanner-legacy/{token}/upload', [App\Http\Controllers\DNIScannerController::class, 'showUpload'])->name('dni.scanner.upload');
Route::post('/dni-scanner/process', [App\Http\Controllers\DNIScannerController::class, 'processImage'])->name('dni.scanner.process');
Route::post('/dni-scanner-legacy/{token}/upload', [App\Http\Controllers\DNIScannerController::class, 'processUpload'])->name('dni.scanner.process.upload');
Route::post('/dni-scanner-legacy/{token}/process-single-image', [App\Http\Controllers\DNIScannerController::class, 'processSingleImage'])->name('dni.scanner.process.single');
Route::post('/dni-scanner-legacy/{token}/save-additional-data', [App\Http\Controllers\DNIScannerController::class, 'saveAdditionalData'])->name('dni.scanner.save.additional');
Route::post('/dni-scanner/complete', [App\Http\Controllers\DNIScannerController::class, 'completeVerification'])->name('dni.scanner.complete');

// Versiones HTML del scanner con mejor diseño (fondo negro)
Route::get('/dni-scanner-simple', function() { return response()->file(public_path('dni-scanner-simple.html')); })->name('dni.scanner.simple');
Route::get('/dni-scanner-fixed', function() { return response()->file(public_path('dni-scanner-fixed.html')); })->name('dni.scanner.fixed');
Route::get('/dni-scanner-fixed-detection', function() { return response()->file(public_path('dni-scanner-fixed-detection.html')); })->name('dni.scanner.fixed.detection');
Route::get('/dni-scanner-no-padding', function() { return response()->file(public_path('dni-scanner-no-padding.html')); })->name('dni.scanner.no.padding');



// AI whatsapp
Route::get('/whatsapp', [App\Http\Controllers\WhatsappController::class, 'hookWhatsapp'])->name('whatsapp.hookWhatsapp');
Route::post('/whatsapp', [App\Http\Controllers\WhatsappController::class, 'processHookWhatsapp'])->name('whatsapp.processHookWhatsapp');
// Route::get('/chatgpt','SiteController@chatGptPruebas')->name('admin.estadisticas.hookWhatsapp');
// Route::get('/cron','SiteController@obtenerAudioMedia2')->name('admin.estadisticas.obtenerAudioMedia2');
Route::get('/chatgpt/{texto?}', [App\Http\Controllers\WhatsappController::class, 'chatGptPruebas'])->name('whatsapp.chatGptPruebas');
Route::get('/cron', [App\Http\Controllers\WhatsappController::class, 'cron'])->name('whatsapp.cron');
Route::post('/whatsapp-envio', [App\Http\Controllers\WhatsappController::class, 'envioAutoVoz'])->name('whatsapp.envioAutoVoz');
//Route::post('/whatsapp-alerta', [App\Http\Controllers\WhatsappController::class, 'envioAlerta'])->name('whatsapp.envioAlerta');

// Rutas varias
Route::get('/gracias/{idioma}', [App\Http\Controllers\GraciasController::class, 'index'])->name('gracias.index');
Route::get('/contacto', [App\Http\Controllers\GraciasController::class, 'contacto'])->name('gracias.contacto');
Route::get('/apartamento-limpio/{token}', [App\Http\Controllers\ApartamentoLimpioController::class, 'show'])->name('apartamento.limpio.show');

Route::get('/mensajes-whatsapp', [App\Http\Controllers\WhatsappController::class, 'whatsapp'])->name('whatsapp.mensajes');
Route::post('/pass-booking', [App\Http\Controllers\ConfiguracionesController::class, 'passBooking'])->name('comprobacion.passBooking');
Route::post('/pass-airbnb', [App\Http\Controllers\ConfiguracionesController::class, 'passAirbnb'])->name('comprobacion.passAirbnb');

Route::post('/gastos-introducir', [App\Http\Controllers\GastosController::class, 'clasificarGastos'])->name('admin.gastos.clasificarGastos');
Route::post('/ingresos-introducir', [App\Http\Controllers\IngresosController::class, 'clasificarIngresos'])->name('admin.ingresos.clasificarIngresos');
Route::post('/get-data', [App\Http\Controllers\ReservasController::class, 'getData'])->name('admin.ingresos.getData');
Route::post('/change-state', [App\Http\Controllers\ReservasController::class, 'changeState'])->name('admin.ingresos.changeState');
Route::get('/facturar-reservas', [App\Http\Controllers\ReservasController::class, 'facturarReservas'])->name('admin.reservas.facturarReservas');
Route::post('/get-reserva-ia/{codigo}', [App\Http\Controllers\ReservasController::class, 'getReservaIA'])->name('admin.reservas.getReservaIA');

Route::get('/probar-ia', [App\Http\Controllers\ReservasController::class, 'probarIA'])->name('probarIA');
Route::get('/instrucciones', [App\Http\Controllers\ReservasController::class, 'mostrarInstrucciones'])->name('mostrarInstrucciones');
Route::post('/guardar-instrucciones', [App\Http\Controllers\ReservasController::class, 'guardarInstrucciones'])->name('guardarInstrucciones');
Route::post('/reservas-cobradas', [App\Http\Controllers\ReservasController::class, 'reservasCobradas'])->name('reservasCobradas');
Route::post('/obtener-reservas', [App\Http\Controllers\ReservasController::class, 'obtenerReservas'])->name('obtenerReservas');


// CHANNEX
Route::prefix('channex')->group(function () {
    Route::get('/full-sync', [App\Http\Controllers\ChannexWebController::class, 'fullSync'])->name('admin.channex.fullSync');
    Route::get('/rate-plans-list', [App\Http\Controllers\ChannexWebController::class, 'ratePlansList'])->name('admin.channex.ratePlansList');

});



// API
Route::post('/obtener-reservas-ia', [App\Http\Controllers\ReservasController::class, 'obtenerReservasIA'])->name('obtenerReservas');
Route::get('/obtener-apartamentos', [App\Http\Controllers\ReservasController::class, 'obtenerApartamentos'])->name('obtenerApartamentos');

Route::get('/chat/send-message', [App\Http\Controllers\ChatController::class, 'sendMessage'])->name('chat.sendMessage');
Route::post('/avisar-tecnico', [App\Http\Controllers\ReservasController::class, 'avisarAveria'])->name('avisarAveria');
Route::post('/avisar-limpieza', [App\Http\Controllers\ReservasController::class, 'avisarLimpieza'])->name('avisarLimpieza');

Route::get('/channex/property', [App\Http\Controllers\ChannexWebController::class, 'index'])->name('channex.propiedad.index');
Route::get('/channex/property/create', [App\Http\Controllers\ChannexWebController::class, 'createProperty'])->name('channex.createPropiedad');
Route::post('/channex/property/store', [App\Http\Controllers\ChannexWebController::class, 'store'])->name('channex.storeProperty');
//Route::post('/channex/room-types/{propertyId}', [App\Http\Controllers\ChannexWebController::class, 'createRoomTypes'])->name('channex.createRoomTypes');
Route::post('/channex/rate-plans', [App\Http\Controllers\ChannexWebController::class, 'createRatePlans'])->name('channex.createRatePlans');
Route::post('/channex/distribution-channels/{propertyId}', [App\Http\Controllers\ChannexWebController::class, 'createDistributionChannels'])->name('channex.createDistributionChannels');
Route::post('/channex/bookings/{channelCode}/{propertyId}/{roomTypeId}', [App\Http\Controllers\ChannexWebController::class, 'createBooking'])->name('channex.createBooking');
Route::post('/channex/bookings/{bookingId}/confirm', [App\Http\Controllers\ChannexWebController::class, 'confirmBooking'])->name('channex.confirmBooking');
Route::post('/upload-photo', [App\Http\Controllers\PhotoController::class, 'upload'])->name('photo.upload');


Route::post('/webhook-handler', [App\Http\Controllers\WebhookController::class, 'handleWebhook']);


// Rate Plans
//Route::resource('/channex/rate-plans', RatePlanController::class);
Route::get('/channex/rate-plans', [App\Http\Controllers\RatePlanController::class, 'index'])->name('channex.ratePlans.index');
Route::get('/channex/rate-plans/create', [App\Http\Controllers\RatePlanController::class, 'create'])->name('channex.ratePlans.create');
Route::get('/channex/rate-plans/edit', [App\Http\Controllers\RatePlanController::class, 'edit'])->name('channex.ratePlans.edit');
Route::post('/channex/rate-plans/store', [App\Http\Controllers\RatePlanController::class, 'store'])->name('channex.ratePlans.store');
Route::post('/channex/rate-plans/destroy', [App\Http\Controllers\RatePlanController::class, 'destroy'])->name('channex.ratePlans.destroy');
Route::post('/channex/rate-plans/update', [App\Http\Controllers\RatePlanController::class, 'update'])->name('channex.ratePlans.update');

Route::resource('/channex/rate-updates', RateUpdateController::class)->only(['create', 'store']);

Route::get('/channex/room-types', [App\Http\Controllers\RoomTypeController::class, 'index'])->name('channex.roomTypes.index');
Route::post('/channex/room-types/store', [App\Http\Controllers\RoomTypeController::class, 'store'])->name('channex.roomTypes.store');
Route::get('/channex/room-types/create', [App\Http\Controllers\RoomTypeController::class, 'create'])->name('channex.roomTypes.create');
Route::get('/channex/room-types/edit', [App\Http\Controllers\RoomTypeController::class, 'edit'])->name('channex.roomTypes.edit');
Route::get('/channex/room-types/destroy', [App\Http\Controllers\RoomTypeController::class, 'destroy'])->name('channex.roomTypes.destroy');
//Route::resource('/channex/room-types', RoomTypeController::class);

Route::get('/channex/channel', [App\Http\Controllers\ChannelController::class, 'index'])->name('channex.channel.index');


Route::get('/channex/ari', [ARIController::class, 'index'])->name('ari.index');
Route::post('/channex/full-sync', [ARIController::class, 'fullSync'])->name('ari.fullSync');

Route::post('/channex/ari/update-rates', [ARIController::class, 'update'])->name('ari.updateRates');
Route::get('/channex/ari/room-types/{property_id}', [ARIController::class, 'getByProperty']);
Route::get('/channex/rate-plans/{propertyId}/{roomTypeId}', [ARIController::class, 'getRatePlans']);

// Rutas para obtener precios diarios
Route::post('/channex/ari/daily-prices', [ARIController::class, 'getDailyPrices'])->name('ari.dailyPrices');
Route::post('/channex/ari/all-daily-prices', [ARIController::class, 'getAllDailyPrices'])->name('ari.allDailyPrices');

// Webhooks
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


Route::get('/templates', [WhatsappTemplateController::class, 'index'])->name('templates.index');
Route::get('/templates/create', [WhatsappTemplateController::class, 'create'])->name('templates.create');
Route::post('/templates', [WhatsappTemplateController::class, 'store'])->name('templates.store');
Route::get('/templates/sync', [WhatsappTemplateController::class, 'sync'])->name('templates.sync');
Route::get('/templates/{template}/status', [WhatsappTemplateController::class, 'checkStatus'])->name('templates.checkStatus');
Route::get('/templates/{template}', [WhatsappTemplateController::class, 'show'])->name('templates.show');
Route::get('/templates/{template}/edit', [WhatsappTemplateController::class, 'edit'])->name('templates.edit');
Route::put('/templates/{template}', [WhatsappTemplateController::class, 'update'])->name('templates.update');

// Rutas para alertas
Route::middleware(['auth'])->group(function () {
    Route::get('/alerts/unread', [App\Http\Controllers\AlertController::class, 'getUnreadAlerts'])->name('alerts.unread');
    Route::post('/alerts/mark-read', [App\Http\Controllers\AlertController::class, 'markAsRead'])->name('alerts.mark-read');
    Route::post('/alerts/mark-all-read', [App\Http\Controllers\AlertController::class, 'markAllAsRead'])->name('alerts.mark-all-read');
    Route::delete('/alerts/{id}', [App\Http\Controllers\AlertController::class, 'destroy'])->name('alerts.destroy');

    // Rutas solo para administradores
    Route::middleware(['auth', 'role:ADMIN'])->group(function () {
        Route::post('/alerts/create', [App\Http\Controllers\AlertController::class, 'create'])->name('alerts.create');
    });
});

// Rutas de API para ARI
Route::middleware(['auth'])->group(function () {
    Route::get('/api/properties/{propertyId}/room-types', function($propertyId) {
        // Usar la misma ruta que usa el sistema ARI existente
        return redirect("/channex/ari/room-types/{$propertyId}");
    });

    Route::get('/api/properties/{propertyId}/room-types/{roomTypeId}/rate-plans', function($propertyId, $roomTypeId) {
        // Usar la misma ruta que usa el sistema ARI existente
        return redirect("/channex/rate-plans/{$propertyId}/{$roomTypeId}");
    });
});

// Admin - Gestión de Limpiezas
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/limpiezas', [App\Http\Controllers\Admin\AdminLimpiezasController::class, 'index'])->name('limpiezas.index');
    Route::get('/limpiezas/{id}', [App\Http\Controllers\Admin\AdminLimpiezasController::class, 'show'])->name('limpiezas.show');
    // [2026-04-17] Marcar limpieza como NO realizada (no-show o estancia prolongada)
    Route::post('/limpiezas/{id}/marcar-no-realizada', [App\Http\Controllers\Admin\AdminLimpiezasController::class, 'marcarNoRealizada'])->name('limpiezas.marcarNoRealizada');

    // Gestión de Zonas Comunes
    Route::resource('zonas-comunes', App\Http\Controllers\Admin\ZonaComunController::class);
    Route::post('/zonas-comunes/{id}/toggle-status', [App\Http\Controllers\Admin\ZonaComunController::class, 'toggleStatus'])->name('zonas-comunes.toggle-status');

    // Gestión de Checklists de Zonas Comunes
    Route::resource('checklists-zonas-comunes', App\Http\Controllers\Admin\ChecklistZonaComunController::class);
    Route::post('/checklists-zonas-comunes/{id}/toggle-status', [App\Http\Controllers\Admin\ChecklistZonaComunController::class, 'toggleStatus'])->name('checklists-zonas-comunes.toggle-status');

    // Sistema de Logs
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [App\Http\Controllers\LogsController::class, 'index'])->name('index');
        Route::get('/files', [App\Http\Controllers\LogsController::class, 'files'])->name('files');
        Route::get('/view/{filename}', [App\Http\Controllers\LogsController::class, 'view'])->name('view');
        Route::get('/download/{filename}', [App\Http\Controllers\LogsController::class, 'download'])->name('download');
        Route::get('/search', [App\Http\Controllers\LogsController::class, 'search'])->name('search');
        Route::post('/clear', [App\Http\Controllers\LogsController::class, 'clear'])->name('clear');
    });
    Route::get('/checklists-zonas-comunes/{id}/items', [App\Http\Controllers\Admin\ChecklistZonaComunController::class, 'manageItems'])->name('checklists-zonas-comunes.items');
    Route::post('/checklists-zonas-comunes/{id}/items', [App\Http\Controllers\Admin\ChecklistZonaComunController::class, 'storeItem'])->name('checklists-zonas-comunes.store-item');

    // Contactos desde la Web
    Route::resource('contactos-web', App\Http\Controllers\Admin\ContactosWebController::class);
    Route::post('/contactos-web/{contactosWeb}/toggle-leido', [App\Http\Controllers\Admin\ContactosWebController::class, 'toggleLeido'])->name('contactos-web.toggle-leido');

    // Pagos y Reservas
    Route::resource('pagos', App\Http\Controllers\Admin\PagosController::class);
    Route::get('/pagos/intentos/listado', [App\Http\Controllers\Admin\PagosController::class, 'intentos'])->name('pagos.intentos');

    // Gestión de Amenities
    Route::resource('amenities', App\Http\Controllers\Admin\AmenityController::class);
    Route::post('/amenities/{id}/toggle-status', [App\Http\Controllers\Admin\AmenityController::class, 'toggleStatus'])->name('amenities.toggle-status');
    Route::post('/amenities/{id}/consumo', [App\Http\Controllers\Admin\AmenityController::class, 'registrarConsumo'])->name('amenities.consumo');
    Route::post('/amenities/{id}/reposicion', [App\Http\Controllers\Admin\AmenityController::class, 'registrarReposicion'])->name('amenities.reposicion');
    Route::post('/amenities/calcular-consumo', [App\Http\Controllers\Admin\AmenityController::class, 'calcularConsumoReserva'])->name('amenities.calcular-consumo');
    Route::get('/amenities/{id}/consumos', [App\Http\Controllers\Admin\AmenityController::class, 'consumos'])->name('amenities.consumos');

    // Sistema de Inventario - Gestión de Proveedores
    Route::resource('proveedores', App\Http\Controllers\Admin\ProveedorController::class);

    // Sistema de Inventario - Gestión de Artículos
    Route::resource('articulos', App\Http\Controllers\Admin\ArticuloController::class);
    Route::post('/articulos/{id}/reponer-stock', [App\Http\Controllers\Admin\ArticuloController::class, 'reponerStock'])->name('articulos.reponer-stock');

    // Sistema de Inventario - Gestión de Movimientos de Stock
    Route::resource('movimientos-stock', App\Http\Controllers\Admin\MovimientoStockController::class);
    Route::get('/movimientos-stock/exportar', [App\Http\Controllers\Admin\MovimientoStockController::class, 'exportar'])->name('movimientos-stock.exportar');
});

// Rutas para panel de limpiadoras
Route::middleware(['auth'])->prefix('limpiadora')->name('limpiadora.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\LimpiadoraDashboardController::class, 'index'])->name('dashboard');
    // Turnos de limpiadoras
    Route::get('/turnos', [App\Http\Controllers\LimpiadoraTurnosController::class, 'index'])->name('turnos.index');
    Route::get('/turnos/{turno}', [App\Http\Controllers\LimpiadoraTurnosController::class, 'show'])->name('turnos.show');
    Route::post('/turnos/{turno}/iniciar', [App\Http\Controllers\LimpiadoraTurnosController::class, 'iniciarTurno'])->name('turnos.iniciar');
    Route::post('/turnos/{turno}/finalizar', [App\Http\Controllers\LimpiadoraTurnosController::class, 'finalizarTurno'])->name('turnos.finalizar');

    // Tareas de limpiadoras
    Route::post('/turnos/tareas/{tarea}/iniciar', [App\Http\Controllers\LimpiadoraTurnosController::class, 'iniciarTarea'])->name('turnos.tareas.iniciar');
    Route::post('/turnos/tareas/{tarea}/completar', [App\Http\Controllers\LimpiadoraTurnosController::class, 'completarTarea'])->name('turnos.tareas.completar');

    // Estadísticas de limpiadoras
    Route::get('/estadisticas', [App\Http\Controllers\LimpiadoraTurnosController::class, 'estadisticas'])->name('estadisticas');

    // Planificación mensual
    Route::get('/planificacion', [App\Http\Controllers\LimpiadoraDashboardController::class, 'planificacion'])->name('planificacion');

    // Cambiar idioma
    Route::get('/cambiar-idioma/{idioma}', [App\Http\Controllers\LimpiadoraDashboardController::class, 'cambiarIdioma'])->name('cambiar-idioma');
});

// Rutas accesibles para limpiadoras (auth sin role:ADMIN)
Route::middleware(['auth'])->group(function () {
    // Incidencias
    Route::get('gestion/incidencias', [App\Http\Controllers\GestionIncidenciasController::class, 'index'])->name('gestion.incidencias.index');
    Route::get('gestion/incidencias/create', [App\Http\Controllers\GestionIncidenciasController::class, 'create'])->name('gestion.incidencias.create');
    Route::post('gestion/incidencias/store', [App\Http\Controllers\GestionIncidenciasController::class, 'store'])->name('gestion.incidencias.store');
    Route::get('gestion/incidencias/{id}', [App\Http\Controllers\GestionIncidenciasController::class, 'show'])->name('gestion.incidencias.show');
    Route::post('gestion/incidencias/{id}/add-photos', [App\Http\Controllers\GestionIncidenciasController::class, 'addPhotos'])->name('gestion.incidencias.add-photos');
    // Foto rapida de limpieza
    Route::post('gestion/limpieza/{id}/foto-rapida', [App\Http\Controllers\GestionApartamentoController::class, 'fotoRapida'])->name('gestion.limpieza.fotoRapida');
});

// Rutas para panel de mantenimiento
Route::middleware(['auth', 'role:MANTENIMIENTO'])->prefix('mantenimiento')->name('mantenimiento.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\MantenimientoDashboardController::class, 'index'])->name('dashboard');
    Route::get('/limpieza/{id}/ver', [App\Http\Controllers\MantenimientoLimpiezaController::class, 'show'])->name('limpieza.ver');
    Route::get('/incidencias', [App\Http\Controllers\MantenimientoIncidenciasController::class, 'index'])->name('incidencias.index');
    Route::get('/incidencias/create', [App\Http\Controllers\MantenimientoIncidenciasController::class, 'create'])->name('incidencias.create');
    Route::post('/incidencias/store', [App\Http\Controllers\MantenimientoIncidenciasController::class, 'store'])->name('incidencias.store');
    Route::post('/incidencias/{incidencia}/resolver', [App\Http\Controllers\MantenimientoIncidenciasController::class, 'resolver'])->name('incidencias.resolver');
    Route::post('/incidencias/{incidencia}/add-photos', [App\Http\Controllers\MantenimientoIncidenciasController::class, 'addPhotos'])->name('incidencias.add-photos');
    Route::get('/incidencias/{incidencia}', [App\Http\Controllers\MantenimientoIncidenciasController::class, 'show'])->name('incidencias.show');
});

// Rutas de Amenities para Limpieza (disponibles para usuarios autenticados)
Route::middleware(['auth'])->group(function () {
    Route::get('/amenities-limpieza/{limpiezaId}', [App\Http\Controllers\AmenityLimpiezaController::class, 'show'])->name('amenity.limpieza.show');
    Route::post('/amenities-limpieza/{limpiezaId}', [App\Http\Controllers\AmenityLimpiezaController::class, 'store'])->name('amenity.limpieza.store');
    Route::get('/amenities/{id}/historial', [App\Http\Controllers\AmenityLimpiezaController::class, 'historial'])->name('amenity.historial');

    // Nueva ruta para cargar amenities de una reserva
Route::get('/amenities-reserva/{reservaId}', [App\Http\Controllers\AmenityLimpiezaController::class, 'getAmenitiesReserva'])->name('amenity.reserva.get');

// Nueva ruta para cargar amenities de una limpieza completada
Route::get('/amenities-limpieza-completada/{limpiezaId}', [App\Http\Controllers\AmenityLimpiezaController::class, 'getAmenitiesLimpiezaCompletada'])->name('amenity.limpieza.completada');
});

// Ruta específica para estadísticas de admin (FUERA del grupo para evitar conflictos)
Route::get('/admin/limpiezas-estadisticas', [App\Http\Controllers\Admin\AdminLimpiezasController::class, 'estadisticas'])->name('admin.limpiezas.estadisticas')->middleware(['auth']);

// Análisis de limpiezas
Route::get('/limpiezas/analisis', [App\Http\Controllers\LimpiezaAnalisisController::class, 'index'])->name('limpiezas.analisis');
Route::get('/limpiezas/estadisticas', [App\Http\Controllers\LimpiezaAnalisisController::class, 'estadisticas'])->name('limpiezas.estadisticas');

Route::get('/gestion/reserva/{id}/info', [App\Http\Controllers\GestionApartamentoController::class, 'mostrarInfoReserva'])->name('gestion.reserva.info');
Route::get('/gestion/limpieza/{id}/ver', [App\Http\Controllers\GestionApartamentoController::class, 'verLimpiezaCompletada'])->name('gestion.limpieza.ver');

// Perfil de Usuario - Accesible para todos los usuarios autenticados
Route::get('/user/profile', [App\Http\Controllers\UserProfileController::class, 'index'])->name('user.profile')->middleware('auth');
Route::post('/user/profile/update', [App\Http\Controllers\UserProfileController::class, 'update'])->name('user.profile.update')->middleware('auth');
Route::post('/user/profile/vacations', [App\Http\Controllers\UserProfileController::class, 'updateVacations'])->name('user.profile.vacations')->middleware('auth');
Route::post('/user/profile/password', [App\Http\Controllers\UserProfileController::class, 'updatePassword'])->name('user.profile.password')->middleware('auth');
Route::post('/user/profile/avatar', [App\Http\Controllers\UserProfileController::class, 'updateAvatar'])->name('user.profile.avatar')->middleware('auth');


// Rutas de notificaciones
Route::prefix('api')->middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/{id}/unread', [NotificationController::class, 'markAsUnread'])->name('notifications.mark-unread');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications/delete-read', [NotificationController::class, 'destroyRead'])->name('notifications.delete-read');
    Route::get('/notifications/stats', [NotificationController::class, 'stats'])->name('notifications.stats');
    Route::get('/notifications/type/{type}', [NotificationController::class, 'byType'])->name('notifications.by-type');
    Route::get('/notifications/priority/{priority}', [NotificationController::class, 'byPriority'])->name('notifications.by-priority');
    Route::get('/notifications/search', [NotificationController::class, 'search'])->name('notifications.search');
    Route::get('/notifications/critical', [NotificationController::class, 'critical'])->name('notifications.critical');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::get('/notifications/expired', [NotificationController::class, 'expired'])->name('notifications.expired');
    Route::delete('/notifications/clean-expired', [NotificationController::class, 'cleanExpired'])->name('notifications.clean-expired');
    Route::get('/notifications/settings', [NotificationController::class, 'settings'])->name('notifications.settings');
    Route::post('/notifications/settings', [NotificationController::class, 'updateSettings'])->name('notifications.update-settings');
});

// Rutas web de notificaciones
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:ADMIN'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
});

// ============================================
// NUEVAS RUTAS ADMIN: Bankinter, Monitor DNI, Conflictos, WhatsApp Templates
// ============================================
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:ADMIN'])->group(function () {

    // Bankinter - Sincronizacion bancaria
    // NOTA: Las rutas POST de sincronizar estan deshabilitadas porque el scraper
    // ahora corre en un PC Windows externo con IP residencial (no en el servidor Coolify).
    // Bankinter bloquea IPs de datacenter, por lo que ejecutar Artisan::call('banco:importar-movimientos')
    // desde el servidor NO funciona. Las unicas rutas utiles son index (estado) y historial (logs).
    Route::prefix('bankinter')->name('bankinter.')->group(function () {
        Route::get('/', [BankinterConfigController::class, 'index'])->name('index');
        // Route::post('/sincronizar-todas', [BankinterConfigController::class, 'sincronizarTodas'])->name('sincronizarTodas');
        // Route::post('/sincronizar/{cuenta}', [BankinterConfigController::class, 'sincronizar'])->name('sincronizar');
        Route::get('/historial', [BankinterConfigController::class, 'historial'])->name('historial');
    });

    // Monitorizacion DNI
    Route::prefix('monitorizacion-dni')->name('monitorizacion-dni.')->group(function () {
        Route::get('/', [MonitorizacionDniController::class, 'index'])->name('index');
        Route::get('/{reservaId}/detalle', [MonitorizacionDniController::class, 'detalle'])->name('detalle');
    });

    // Conflictos de Reservas
    Route::prefix('conflictos-reservas')->name('conflictos-reservas.')->group(function () {
        Route::get('/', [ConflictosReservasController::class, 'index'])->name('index');
        Route::get('/{id}/detalle', [ConflictosReservasController::class, 'detalle'])->name('detalle');
        Route::post('/{id}/resolver', [ConflictosReservasController::class, 'resolver'])->name('resolver');
    });

    // Plantillas WhatsApp (admin views)
    // Prompts de IA (WhatsApp y Channex)
    Route::get('/prompt/{tipo}', [App\Http\Controllers\PromptController::class, 'edit'])->name('prompt.edit');
    Route::post('/prompt/{tipo}', [App\Http\Controllers\PromptController::class, 'update'])->name('prompt.update');

    Route::prefix('whatsapp-templates')->name('whatsapp-templates.')->group(function () {
        Route::get('/', [WhatsappTemplateController::class, 'index'])->name('index');
        Route::get('/create', [WhatsappTemplateController::class, 'create'])->name('create');
        Route::post('/', [WhatsappTemplateController::class, 'store'])->name('store');
        Route::get('/sync', [WhatsappTemplateController::class, 'sync'])->name('sync');
        Route::get('/{whatsappTemplate}/edit', [WhatsappTemplateController::class, 'edit'])->name('edit');
        Route::put('/{whatsappTemplate}', [WhatsappTemplateController::class, 'update'])->name('update');
        Route::delete('/{whatsappTemplate}', [WhatsappTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{whatsappTemplate}/test', [WhatsappTemplateController::class, 'test'])->name('test');
        Route::get('/{whatsappTemplate}/check-status', [WhatsappTemplateController::class, 'checkStatus'])->name('check-status');
    });

    // Conversaciones Channex (Booking/Airbnb)
    Route::get('/channex-mensajes', [App\Http\Controllers\ChannexMensajesController::class, 'index'])->name('channex-mensajes.index');
    Route::get('/channex-mensajes/{bookingId}', [App\Http\Controllers\ChannexMensajesController::class, 'mensajes'])->name('channex-mensajes.mensajes');

    // Centro de Alertas / Comunicaciones
    Route::get('/comunicacion/alertas', [App\Http\Controllers\AlertasCentralController::class, 'index'])->name('alertas.index');
    Route::get('/comunicacion/alertas/historial', [App\Http\Controllers\AlertasCentralController::class, 'historial'])->name('alertas.historial');
    Route::get('/comunicacion/alertas/detalle', [App\Http\Controllers\AlertasCentralController::class, 'detalle'])->name('admin.alertas.detalle');
    Route::get('/comunicacion/alertas/plantillas', [App\Http\Controllers\AlertasCentralController::class, 'plantillas'])->name('admin.alertas.plantillas');
    Route::get('/comunicacion/alertas/mensajes-ota', [App\Http\Controllers\AlertasCentralController::class, 'mensajesOTA'])->name('admin.alertas.mensajesOTA');
    Route::get('/comunicacion/alertas/emails', [App\Http\Controllers\AlertasCentralController::class, 'emailsEnviados'])->name('admin.alertas.emails');
});

// Descargas temporales (publico, sin auth)
Route::get("/descargas/asesoria/{token}", [App\Http\Controllers\DescargaTemporalController::class, "descargar"])->name("descarga.temporal");

// Login limpiadoras (publico, sin certificado - solo nombre + contraseña)
Route::get('/limpieza', [App\Http\Controllers\LimpiadoraLoginController::class, 'showLogin'])->name('limpiadora.login');
Route::post('/limpieza', [App\Http\Controllers\LimpiadoraLoginController::class, 'login'])->name('limpiadora.login.post');
