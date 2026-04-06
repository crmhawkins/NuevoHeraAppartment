<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class PublicAuthController extends Controller
{
    /**
     * Mostrar formulario de login público
     */
    public function showLoginForm()
    {
        if (Auth::guard('cliente')->check()) {
            return redirect()->route('web.perfil');
        }
        return view('public.auth.login');
    }

    /**
     * Procesar login público
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'identificador' => 'required|string', // Puede ser email o teléfono
            'password' => 'nullable|string', // Opcional si no tiene password
        ]);

        $remember = $request->has('remember');

        // Buscar cliente por email o teléfono
        $cliente = Cliente::buscarPorCredenciales($validated['identificador']);

        if (!$cliente) {
            return back()->withErrors([
                'identificador' => 'No encontramos una cuenta con esos datos.',
            ])->withInput($request->only('identificador'));
        }

        // Si el cliente tiene password, verificar
        if ($cliente->tienePassword()) {
            if (!Hash::check($validated['password'], $cliente->password)) {
                return back()->withErrors([
                    'password' => 'La contraseña no es correcta.',
                ])->withInput($request->only('identificador'));
            }
            
            // Login exitoso con password
            Auth::guard('cliente')->login($cliente, $remember);
            $request->session()->regenerate();
            
            return redirect()->intended(route('web.perfil'));
        } else {
            // Cliente sin password - redirigir a establecer password
            // Guardar identificador en sesión para el siguiente paso
            $request->session()->put('cliente_sin_password_id', $cliente->id);
            $request->session()->put('cliente_sin_password_identificador', $validated['identificador']);
            
            return redirect()->route('web.establecer-password');
        }
    }

    /**
     * Mostrar formulario de registro público
     */
    public function showRegisterForm()
    {
        if (Auth::guard('cliente')->check()) {
            return redirect()->route('web.perfil');
        }
        return view('public.auth.register');
    }

    /**
     * Procesar registro público
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido1' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verificar si ya existe un cliente con ese email
        $clienteExistente = Cliente::where('email', $request->email)
            ->orWhere('email_secundario', $request->email)
            ->first();

        if ($clienteExistente) {
            // Si existe pero no tiene password, redirigir a establecer password
            if (!$clienteExistente->tienePassword()) {
                $request->session()->put('cliente_sin_password_id', $clienteExistente->id);
                $request->session()->put('cliente_sin_password_identificador', $request->email);
                return redirect()->route('web.establecer-password')
                    ->with('info', 'Ya existe una cuenta con este email. Por favor, establece tu contraseña.');
            }
            
            return back()->withErrors([
                'email' => 'Ya existe una cuenta con este email. Por favor, inicia sesión.',
            ])->withInput();
        }

        // Crear nuevo cliente
        $cliente = Cliente::create([
            'nombre' => $request->nombre,
            'apellido1' => $request->apellido1,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'password' => Hash::make($request->password),
            'password_set_at' => now(),
        ]);

        Auth::guard('cliente')->login($cliente);

        Alert::success('¡Bienvenido!', 'Tu cuenta ha sido creada correctamente.');
        return redirect()->route('web.perfil');
    }

    /**
     * Mostrar formulario para verificar cuenta y establecer password
     */
    public function showVerificarCuentaForm()
    {
        return view('public.auth.verificar-cuenta');
    }

    /**
     * Procesar verificación de cuenta
     */
    public function verificarCuenta(Request $request)
    {
        $validated = $request->validate([
            'identificador' => 'required|string', // Puede ser email o teléfono
        ]);

        // Buscar cliente por email o teléfono
        $cliente = Cliente::buscarPorCredenciales($validated['identificador']);

        if (!$cliente) {
            return back()->withErrors([
                'identificador' => 'No encontramos una cuenta con esos datos. ¿Quieres crear una cuenta nueva?',
            ])->withInput();
        }

        // Si el cliente ya tiene password, redirigir a login
        if ($cliente->tienePassword()) {
            return redirect()->route('web.login')
                ->with('info', 'Ya tienes una contraseña establecida. Por favor, inicia sesión con tu contraseña.')
                ->withInput(['identificador' => $validated['identificador']]);
        }

        // Guardar cliente en sesión para establecer password
        $request->session()->put('cliente_sin_password_id', $cliente->id);
        $request->session()->put('cliente_sin_password_identificador', $validated['identificador']);

        return redirect()->route('web.establecer-password');
    }

    /**
     * Mostrar formulario para establecer password (clientes existentes)
     */
    public function showEstablecerPasswordForm(Request $request)
    {
        $clienteId = $request->session()->get('cliente_sin_password_id');
        
        if (!$clienteId) {
            return redirect()->route('web.verificar-cuenta')
                ->with('error', 'Sesión expirada. Por favor, verifica tu cuenta nuevamente.');
        }

        $cliente = Cliente::find($clienteId);
        
        if (!$cliente) {
            $request->session()->forget('cliente_sin_password_id');
            return redirect()->route('web.verificar-cuenta')
                ->with('error', 'Cliente no encontrado.');
        }

        return view('public.auth.establecer-password', compact('cliente'));
    }

    /**
     * Procesar establecimiento de password
     */
    public function establecerPassword(Request $request)
    {
        $clienteId = $request->session()->get('cliente_sin_password_id');
        
        if (!$clienteId) {
            return redirect()->route('web.login')
                ->with('error', 'Sesión expirada.');
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
            'verificacion_email' => 'required|email',
        ]);

        $cliente = Cliente::find($clienteId);
        
        if (!$cliente) {
            $request->session()->forget('cliente_sin_password_id');
            return redirect()->route('web.login')
                ->with('error', 'Cliente no encontrado.');
        }

        // Verificar que el email de verificación coincida con el email del cliente
        $emailPrincipal = $cliente->email_principal;
        
        if ($validated['verificacion_email'] !== $emailPrincipal) {
            return back()->withErrors([
                'verificacion_email' => 'El email de verificación no coincide con el email de tu cuenta.',
            ])->withInput();
        }

        // Establecer password
        $cliente->update([
            'password' => Hash::make($validated['password']),
            'password_set_at' => now(),
        ]);

        // Limpiar sesión
        $request->session()->forget('cliente_sin_password_id');
        $request->session()->forget('cliente_sin_password_identificador');

        // Login automático
        Auth::guard('cliente')->login($cliente);

        Alert::success('¡Éxito!', 'Tu contraseña ha sido establecida correctamente.');
        return redirect()->route('web.perfil');
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        Auth::guard('cliente')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('web.index');
    }
}
