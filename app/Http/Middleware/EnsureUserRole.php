<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserRole
{
    public function handle( Request $request, Closure $next, ...$roles )
    {
        if ( !Auth::check() ) {
            // El usuario no está autenticado
            return redirect( 'login' );
        }

        $user = Auth::user();
        foreach ( $roles as $role ) {
            if ( $user->role === $role ) {
                return $next( $request );
            }
        }

        // Si el usuario no tiene el rol necesario, redirecciona o muestra un error
        abort( 403, 'No tienes permiso para acceder a esta página.' );
    }
}
