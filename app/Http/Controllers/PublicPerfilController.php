<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use RealRashid\SweetAlert\Facades\Alert;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentMethod;

class PublicPerfilController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:cliente');
    }

    /**
     * Mostrar perfil del cliente
     */
    public function index()
    {
        $cliente = Auth::guard('cliente')->user();
        
        // Verificar si viene de una reserva
        $returnToReserva = request()->get('return_to_reserva', false);
        $reservaParams = session('reserva_params');
        
        // Obtener reservas del cliente
        $reservas = Reserva::where('cliente_id', $cliente->id)
            ->with(['apartamento', 'cliente', 'estado', 'pagos', 'serviciosExtras'])
            ->orderBy('fecha_entrada', 'desc')
            ->get();
        
        // Separar reservas activas y anteriores
        $reservasActivas = $reservas->filter(function($reserva) {
            return $reserva->fecha_salida >= now() && 
                   in_array($reserva->estado_id, [1, 2, 3]); // Pendiente, Confirmada, En curso
        });
        
        $reservasAnteriores = $reservas->filter(function($reserva) {
            return $reserva->fecha_salida < now() || 
                   in_array($reserva->estado_id, [4, 5]); // Completada, Cancelada
        });
        
        // Obtener métodos de pago de Stripe si existe customer_id
        $paymentMethods = null;
        if ($cliente->stripe_customer_id && config('services.stripe.secret')) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $paymentMethods = PaymentMethod::all([
                    'customer' => $cliente->stripe_customer_id,
                    'type' => 'card',
                ]);
            } catch (\Exception $e) {
                // Error al obtener métodos de pago
                $paymentMethods = null;
            }
        }
        
        return view('public.perfil.index', compact('cliente', 'reservasActivas', 'reservasAnteriores', 'paymentMethods', 'returnToReserva', 'reservaParams'));
    }

    /**
     * Actualizar información personal
     */
    public function updatePerfil(Request $request)
    {
        $cliente = Auth::guard('cliente')->user();
        
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido1' => 'nullable|string|max:255',
            'apellido2' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'telefono_movil' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'localidad' => 'nullable|string|max:255',
            'codigo_postal' => 'nullable|string|max:10',
            'provincia' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'nacionalidad' => 'nullable|string|max:3',
            'tipo_documento' => 'nullable|string|in:DNI,NIE,PASAPORTE',
            'num_identificacion' => 'nullable|string|max:20',
            'fecha_expedicion_doc' => 'nullable|date',
            'sexo' => 'nullable|string|in:Masculino,Femenino',
            'lugar_nacimiento' => 'nullable|string|max:255',
        ]);
        
        // Guardar el email antes de eliminarlo del array
        $emailNuevo = $validated['email'];
        $emailPrincipal = $cliente->email_principal;
        
        // Remover email del array para no duplicar la lógica
        unset($validated['email']);
        
        // Convertir strings vacíos a null para campos nullable
        foreach ($validated as $key => $value) {
            if ($value === '') {
                $validated[$key] = null;
            }
        }
        
        // Si no tiene telefono_movil pero tiene telefono, copiarlo
        if (empty($validated['telefono_movil']) && !empty($validated['telefono'])) {
            $validated['telefono_movil'] = $validated['telefono'];
        }
        
        // Actualizar campos uno por uno para asegurar que se guarden
        $cliente->telefono_movil = $validated['telefono_movil'] ?? null;
        $cliente->lugar_nacimiento = $validated['lugar_nacimiento'] ?? null;
        $cliente->nombre = $validated['nombre'];
        $cliente->apellido1 = $validated['apellido1'] ?? null;
        $cliente->apellido2 = $validated['apellido2'] ?? null;
        $cliente->telefono = $validated['telefono'] ?? null;
        $cliente->direccion = $validated['direccion'] ?? null;
        $cliente->localidad = $validated['localidad'] ?? null;
        $cliente->codigo_postal = $validated['codigo_postal'] ?? null;
        $cliente->provincia = $validated['provincia'] ?? null;
        $cliente->fecha_nacimiento = $validated['fecha_nacimiento'] ?? null;
        $cliente->nacionalidad = $validated['nacionalidad'] ?? null;
        $cliente->tipo_documento = $validated['tipo_documento'] ?? null;
        $cliente->num_identificacion = $validated['num_identificacion'] ?? null;
        $cliente->fecha_expedicion_doc = $validated['fecha_expedicion_doc'] ?? null;
        $cliente->sexo = $validated['sexo'] ?? null;
        
        // Guardar explícitamente
        $cliente->save();
        
        // Actualizar email principal o secundario según corresponda
        if ($emailNuevo !== $emailPrincipal) {
            // Si el email principal es de booking, usar email_secundario
            if ($cliente->esEmailBooking($cliente->email)) {
                $cliente->email_secundario = $emailNuevo;
            } else {
                $cliente->email = $emailNuevo;
            }
            $cliente->save();
        }
        
        Alert::success('Éxito', 'Tu información personal ha sido actualizada.');
        
        // Si viene de una reserva, redirigir de vuelta
        $reservaParams = session('reserva_params');
        if ($reservaParams) {
            return redirect()->route('web.reservas.formulario', [
                'apartamento' => $reservaParams['apartamento_id'],
                'fecha_entrada' => $reservaParams['fecha_entrada'],
                'fecha_salida' => $reservaParams['fecha_salida'],
                'adultos' => $reservaParams['adultos'],
                'ninos' => $reservaParams['ninos'],
                'es_para_mi' => $reservaParams['es_para_mi'],
            ]);
        }
        
        return redirect()->route('web.perfil');
    }

    /**
     * Actualizar contraseña
     */
    public function updatePassword(Request $request)
    {
        $cliente = Auth::guard('cliente')->user();
        
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        if (!Hash::check($validated['current_password'], $cliente->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }
        
        $cliente->update([
            'password' => Hash::make($validated['password']),
            'password_set_at' => now(),
        ]);
        
        Alert::success('Éxito', 'Tu contraseña ha sido actualizada.');
        return redirect()->route('web.perfil');
    }

    /**
     * Guardar método de pago en Stripe
     */
    public function guardarMetodoPago(Request $request)
    {
        $cliente = Auth::guard('cliente')->user();
        
        $validated = $request->validate([
            'payment_method_id' => 'required|string',
        ]);
        
        if (!config('services.stripe.secret')) {
            Alert::error('Error', 'Stripe no está configurado.');
            return back();
        }
        
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            
            // Crear o recuperar customer
            if (!$cliente->stripe_customer_id) {
                $customer = Customer::create([
                    'email' => $cliente->email_principal,
                    'name' => trim($cliente->nombre . ' ' . $cliente->apellido1 . ' ' . $cliente->apellido2),
                    'metadata' => [
                        'cliente_id' => $cliente->id,
                    ],
                ]);
                
                $cliente->update(['stripe_customer_id' => $customer->id]);
            } else {
                $customer = Customer::retrieve($cliente->stripe_customer_id);
            }
            
            // Adjuntar método de pago al customer
            $paymentMethod = PaymentMethod::retrieve($validated['payment_method_id']);
            $paymentMethod->attach(['customer' => $customer->id]);
            
            // Establecer como método por defecto si es el primero
            if (!$customer->invoice_settings->default_payment_method) {
                Customer::update($customer->id, [
                    'invoice_settings' => [
                        'default_payment_method' => $validated['payment_method_id'],
                    ],
                ]);
            }
            
            Alert::success('Éxito', 'Método de pago guardado correctamente.');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo guardar el método de pago: ' . $e->getMessage());
        }
        
        return redirect()->route('web.perfil');
    }

    /**
     * Eliminar método de pago
     */
    public function eliminarMetodoPago(Request $request, $paymentMethodId)
    {
        $cliente = Auth::guard('cliente')->user();
        
        if (!config('services.stripe.secret')) {
            Alert::error('Error', 'Stripe no está configurado.');
            return back();
        }
        
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->detach();
            
            Alert::success('Éxito', 'Método de pago eliminado correctamente.');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo eliminar el método de pago: ' . $e->getMessage());
        }
        
        return redirect()->route('web.perfil');
    }

    /**
     * Mostrar detalles de una reserva
     */
    public function showReserva($id)
    {
        $cliente = Auth::guard('cliente')->user();
        
        // Verificar que la reserva pertenece al cliente
        $reserva = Reserva::where('id', $id)
            ->where('cliente_id', $cliente->id)
            ->with(['apartamento', 'cliente', 'estado', 'pagos', 'serviciosExtras.servicio'])
            ->firstOrFail();
        
        return view('public.perfil.reserva-detalle', compact('reserva', 'cliente'));
    }
}
