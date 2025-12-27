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
     * Controla el acceso al dashboard basándose en permisos.
     * Redirige a usuarios sin permisos a su vista principal correspondiente.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Verificar si el usuario tiene permiso para ver el dashboard
        if (user_can('dashboard.view')) {
            return $next($request);
        }

        // Si no tiene permiso de dashboard, redirigir según su rol/permisos

        // 1. Si tiene acceso al POS, redirigir allí
        if (user_can('pos.use')) {
            return redirect()->route('pos.login')
                ->with('info', 'Bienvenido al Punto de Venta');
        }

        // 2. Si puede ver ventas, redirigir al listado de ventas
        if (user_can('sales.view')) {
            return redirect()->route('sales.index')
                ->with('info', 'Bienvenido al sistema de ventas');
        }

        // 3. Si puede ver reportes, redirigir al centro de reportes
        if (user_can('reports.view')) {
            return redirect()->route('reports.index')
                ->with('info', 'Bienvenido al centro de reportes');
        }

        // 4. Si puede ver productos, redirigir allí
        if (user_can('products.view')) {
            return redirect()->route('products.index')
                ->with('info', 'Bienvenido al sistema de inventario');
        }

        // 5. Si tiene algún permiso de contabilidad, redirigir al plan de cuentas
        if (user_can('account-chart.view')) {
            return redirect()->route('account-chart.index')
                ->with('info', 'Bienvenido al módulo de contabilidad');
        }

        // 6. Si no tiene ningún permiso específico, mostrar mensaje de error
        abort(403, 'No tiene permisos suficientes para acceder al sistema. Contacte al administrador.');
    }
}
