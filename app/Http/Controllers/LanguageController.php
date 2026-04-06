<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class LanguageController extends Controller
{
    /**
     * Cambiar el idioma de la aplicación
     */
    public function changeLanguage(Request $request, $locale)
    {
        // Validar que el idioma sea válido
        $idiomasValidos = ['es', 'en', 'fr', 'de', 'it', 'pt'];
        
        if (!in_array($locale, $idiomasValidos)) {
            Log::warning('Intento de cambiar a idioma no válido', ['locale' => $locale]);
            $locale = 'es'; // Default a español
        }
        
        // Guardar el idioma en la sesión (ambas claves para compatibilidad)
        Session::put('locale', $locale);
        Session::put('idioma', $locale); // Mantener compatibilidad
        
        // Forzar guardado de la sesión ANTES de establecer el locale
        Session::save();
        
        // Establecer el locale en la aplicación inmediatamente
        App::setLocale($locale);
        
        // Verificar que se guardó
        Log::info('Idioma guardado en sesión', [
            'locale' => $locale,
            'session_locale' => Session::get('locale'),
            'session_id' => Session::getId()
        ]);
        
        // Si el usuario está autenticado como cliente, guardar su preferencia
        if (auth('cliente')->check()) {
            $cliente = auth('cliente')->user();
            $cliente->update([
                'idioma' => $locale,
                'idioma_establecido' => true
            ]);
        }
        
        Log::info('Idioma cambiado', [
            'locale' => $locale,
            'user_id' => auth('cliente')->id(),
            'session_id' => Session::getId(),
            'session_locale' => Session::get('locale')
        ]);
        
        // Obtener la URL de retorno o usar la home
        $redirectUrl = $request->header('referer') ?: route('web.index');
        
        // Redirigir a la URL de retorno con el locale en la sesión
        return redirect($redirectUrl)->with('success', __('Idioma cambiado correctamente'));
    }
}
