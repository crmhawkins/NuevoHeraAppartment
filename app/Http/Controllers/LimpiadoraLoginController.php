<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LimpiadoraLoginController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->role === 'LIMPIEZA') {
            return redirect('/limpiadora/dashboard');
        }

        return view('limpiadora.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'password' => 'required|string',
        ]);

        $nombre = trim($request->nombre);

        // Search by name (case-insensitive) among LIMPIEZA users
        $user = User::where('role', 'LIMPIEZA')
            ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($nombre) . '%'])
            ->first();

        if (!$user) {
            return back()->withErrors(['nombre' => 'No se encontró ninguna limpiadora con ese nombre.'])->withInput();
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['nombre' => 'Contraseña incorrecta.'])->withInput();
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect('/limpiadora/dashboard');
    }
}
