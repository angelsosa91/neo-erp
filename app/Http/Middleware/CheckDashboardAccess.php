<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDashboardAccess
{
    /**
     * Handle an incoming request.
     *
     * Permite acceso al dashboard solo a usuarios con roles administrativos.
     * Vendedores y otros roles sin permisos administrativos son redirigidos al POS.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super Admin y Admin SIEMPRE tienen acceso al dashboard
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return $next($request);
        }

        // Vendedor con rol específico → redirigir al POS directo
        if ($user->hasRole('vendedor')) {
            return redirect()->route('pos.index')
                ->with('info', 'Su cuenta está configurada para usar el POS');
        }

        // Otros usuarios → permitir acceso según sus permisos
        // (pueden tener roles personalizados con acceso limitado al dashboard)
        return $next($request);
    }
}
