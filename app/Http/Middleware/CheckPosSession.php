<?php

namespace App\Http\Middleware;

use App\Models\PosSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPosSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtener token de sesión
        $sessionToken = session('pos_session_token');

        // Si no hay token, redirigir a login
        if (!$sessionToken) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay sesión POS activa',
                    'redirect' => route('pos.login'),
                ], 401);
            }

            return redirect()->route('pos.login')
                ->with('error', 'Debe iniciar sesión en el POS');
        }

        // Buscar sesión
        $posSession = PosSession::where('session_token', $sessionToken)->first();

        // Si no existe la sesión, limpiar y redirigir
        if (!$posSession) {
            session()->forget('pos_session_token');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión no encontrada',
                    'redirect' => route('pos.login'),
                ], 401);
            }

            return redirect()->route('pos.login')
                ->with('error', 'Sesión no encontrada');
        }

        // Verificar si la sesión ha expirado (timeout: 10 minutos)
        if ($posSession->isExpired(10)) {
            $posSession->markAsExpired();
            session()->forget('pos_session_token');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión expirada por inactividad',
                    'redirect' => route('pos.login'),
                    'expired' => true,
                ], 401);
            }

            return redirect()->route('pos.login')
                ->with('error', 'Sesión expirada por inactividad. Por favor, inicie sesión nuevamente.');
        }

        // Actualizar timestamp de última actividad
        $posSession->updateActivity();

        // Compartir información de la sesión con las vistas
        view()->share('posSession', $posSession);
        view()->share('posUser', $posSession->user);

        return $next($request);
    }
}
