<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\ReservaServicio;
use App\Models\Pago;
use App\Models\IntentoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservaExtrasController extends Controller
{
    /**
     * Mostrar formulario para buscar reserva por código
     */
    public function buscarReserva()
    {
        return view('public.extras.buscar-reserva');
    }

    /**
     * Buscar reserva por código y mostrar servicios disponibles
     */
    public function mostrarServicios(Request $request)
    {
        $request->validate([
            'codigo_reserva' => 'required|string|max:50',
        ]);

        $reserva = Reserva::where('codigo_reserva', $request->codigo_reserva)
            ->with(['cliente', 'apartamento'])
            ->first();

        if (!$reserva) {
            return back()->with('error', 'No se encontró ninguna reserva con ese código. Por favor, verifica el código e inténtalo de nuevo.')->withInput();
        }

        // Obtener servicios activos con precio (extras comprables)
        $servicios = Servicio::activos()
            ->whereNotNull('precio')
            ->where('precio', '>', 0)
            ->ordenados()
            ->get();

        // Obtener servicios ya comprados para esta reserva
        $serviciosComprados = $reserva->serviciosExtras()
            ->where('estado', 'pagado')
            ->with('servicio')
            ->get()
            ->pluck('servicio_id')
            ->toArray();

        return view('public.extras.seleccionar-servicios', compact('reserva', 'servicios', 'serviciosComprados'));
    }

    /**
     * Procesar compra de extras
     */
    public function procesarCompra(Request $request)
    {
        $request->validate([
            'reserva_id' => 'required|exists:reservas,id',
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'required|exists:servicios,id',
        ]);

        try {
            $reserva = Reserva::with('cliente')->findOrFail($request->reserva_id);
            $serviciosIds = $request->servicios;
            
            // Obtener servicios seleccionados
            $servicios = Servicio::whereIn('id', $serviciosIds)->get();
            
            if ($servicios->isEmpty()) {
                return back()->with('error', 'No se seleccionaron servicios válidos.')->withInput();
            }

            // Calcular total
            $total = $servicios->sum('precio');

            // Verificar Stripe
            $stripeSecret = config('services.stripe.secret');
            
            if (!$stripeSecret) {
                \Log::error('Stripe secret key no configurada');
                return back()->with('error', 'El sistema de pagos no está configurado. Por favor, contacta con nosotros.')->withInput();
            }
            
            if (!class_exists('\Stripe\Stripe')) {
                \Log::error('Stripe SDK no disponible');
                return back()->with('error', 'El sistema de pagos no está disponible. Por favor, contacta con nosotros.')->withInput();
            }

            // Usar transacción
            return DB::transaction(function () use ($reserva, $servicios, $total, $stripeSecret, $request) {
                // Crear registros de reserva_servicios (pendientes)
                $reservaServicios = [];
                foreach ($servicios as $servicio) {
                    $reservaServicios[] = ReservaServicio::create([
                        'reserva_id' => $reserva->id,
                        'servicio_id' => $servicio->id,
                        'precio' => $servicio->precio,
                        'moneda' => 'EUR',
                        'estado' => 'pendiente',
                    ]);
                }

                // Crear pago para los extras
                $pago = Pago::create([
                    'reserva_id' => $reserva->id,
                    'cliente_id' => $reserva->cliente_id,
                    'metodo_pago' => 'stripe',
                    'estado' => 'pendiente',
                    'monto' => $total,
                    'moneda' => 'EUR',
                    'descripcion' => "Extras para reserva {$reserva->codigo_reserva}",
                    'metadata' => [
                        'tipo' => 'extras',
                        'servicios' => $servicios->pluck('nombre')->toArray(),
                    ],
                ]);

                // Actualizar reserva_servicios con el pago_id
                foreach ($reservaServicios as $reservaServicio) {
                    $reservaServicio->update(['pago_id' => $pago->id]);
                }

                // Crear sesión de Stripe
                \Stripe\Stripe::setApiKey($stripeSecret);
                
                $lineItems = $servicios->map(function ($servicio) {
                    return [
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => [
                                'name' => $servicio->nombre,
                                'description' => $servicio->descripcion,
                            ],
                            'unit_amount' => (int)($servicio->precio * 100),
                        ],
                        'quantity' => 1,
                    ];
                })->toArray();

                $checkoutSession = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => $lineItems,
                    'mode' => 'payment',
                    'success_url' => route('web.extras.pago.exito') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('web.extras.pago.cancelado') . '?reserva_id=' . $reserva->id,
                    'customer_email' => $reserva->cliente->email ?? null,
                    'metadata' => [
                        'reserva_id' => $reserva->id,
                        'pago_id' => $pago->id,
                        'codigo_reserva' => $reserva->codigo_reserva,
                        'tipo' => 'extras',
                    ],
                ]);

                // Actualizar pago
                $pago->update([
                    'stripe_checkout_session_id' => $checkoutSession->id,
                ]);

                // Actualizar reserva_servicios con session ID
                foreach ($reservaServicios as $reservaServicio) {
                    $reservaServicio->update([
                        'stripe_checkout_session_id' => $checkoutSession->id,
                    ]);
                }

                // Registrar intento de pago
                IntentoPago::create([
                    'pago_id' => $pago->id,
                    'reserva_id' => $reserva->id,
                    'stripe_checkout_session_id' => $checkoutSession->id,
                    'estado' => 'iniciado',
                    'monto' => $total,
                    'moneda' => 'EUR',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'fecha_intento' => now(),
                ]);

                \Log::info('Redirigiendo a Stripe Checkout para extras', [
                    'session_id' => $checkoutSession->id,
                    'reserva_id' => $reserva->id,
                    'pago_id' => $pago->id,
                ]);

                return redirect($checkoutSession->url);
            });

        } catch (\Stripe\Exception\ApiErrorException $e) {
            \Log::error('Error al crear sesión de Stripe para extras: ' . $e->getMessage());
            return back()->with('error', 'Error al procesar el pago: ' . $e->getMessage() . '. Por favor, inténtalo de nuevo.')->withInput();
        } catch (\Exception $e) {
            \Log::error('Error al procesar compra de extras: ' . $e->getMessage());
            return back()->with('error', 'Hubo un error al procesar tu compra. Por favor, inténtalo de nuevo.')->withInput();
        }
    }

    /**
     * Página de éxito después del pago de extras
     */
    public function exito(Request $request)
    {
        $sessionId = $request->get('session_id');
        
        if (!$sessionId) {
            return redirect()->route('web.index')->with('error', 'Sesión de pago no válida.');
        }

        try {
            if (class_exists('\Stripe\Stripe')) {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                $session = \Stripe\Checkout\Session::retrieve($sessionId);
                
                $pago = Pago::where('stripe_checkout_session_id', $sessionId)->first();
                
                if ($pago && $session->payment_status === 'paid') {
                    // El webhook debería haber actualizado esto, pero por si acaso
                    if ($pago->estado !== 'completado') {
                        $pago->update([
                            'estado' => 'completado',
                            'fecha_pago' => now(),
                            'stripe_payment_intent_id' => $session->payment_intent,
                        ]);
                        
                        // Actualizar reserva_servicios a pagado
                        ReservaServicio::where('pago_id', $pago->id)
                            ->update([
                                'estado' => 'pagado',
                                'fecha_pago' => now(),
                                'stripe_payment_intent_id' => $session->payment_intent,
                            ]);
                    }

                    // Notificar al equipo si es early check-in o late checkout
                    $reserva = $pago->reserva;
                    $reservaServicios = ReservaServicio::where('pago_id', $pago->id)->with('servicio')->get();
                    foreach ($reservaServicios as $rs) {
                        $servicio = $rs->servicio;
                        if ($servicio && (
                            stripos($servicio->nombre, 'early') !== false ||
                            stripos($servicio->nombre, 'late') !== false ||
                            stripos($servicio->nombre, 'temprano') !== false ||
                            stripos($servicio->nombre, 'tardío') !== false ||
                            stripos($servicio->nombre, 'tardio') !== false
                        )) {
                            try {
                                \App\Services\AlertaEquipoService::alertar(
                                    'SERVICIO EXTRA CONTRATADO',
                                    "Reserva: {$reserva->codigo_reserva}\n"
                                    . "Servicio: {$servicio->nombre}\n"
                                    . "Apartamento: " . ($reserva->apartamento->titulo ?? 'N/A') . "\n"
                                    . "Entrada: {$reserva->fecha_entrada}\n"
                                    . "Salida: {$reserva->fecha_salida}\n"
                                    . "PRIORIZAR LIMPIEZA DE ESTE APARTAMENTO",
                                    'servicio_extra'
                                );
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Error notificando servicio extra: ' . $e->getMessage());
                            }
                        }
                    }

                    return view('public.extras.compra-exitosa', [
                        'reserva' => $pago->reserva,
                        'pago' => $pago,
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error al verificar pago exitoso de extras: ' . $e->getMessage());
        }

        return redirect()->route('web.index')->with('error', 'No se pudo verificar el pago.');
    }

    /**
     * Página de cancelación
     */
    public function cancelado(Request $request)
    {
        $reservaId = $request->get('reserva_id');
        
        return view('public.extras.compra-cancelada', [
            'reserva_id' => $reservaId,
        ]);
    }
}
