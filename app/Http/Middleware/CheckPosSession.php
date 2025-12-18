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
        $user = $request->user();

        // Obtener token de sesión
        $sessionToken = session('pos_session_token');

        // Si no hay token, verificar si es vendedor (acceso directo)
        if (!$sessionToken) {
            // Si es vendedor, crear sesión automáticamente
            if ($user && $user->hasRole('vendedor')) {
                // Cerrar sesiones anteriores
                PosSession::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->each(function ($session) {
                        $session->close();
                    });

                // Crear nueva sesión automática para vendedor
                $posSession = PosSession::createSession(
                    $user,
                    'auto-login', // Método: login automático (sin PIN)
                    null,
                    null
                );

                // Guardar token en sesión
                session(['pos_session_token' => $posSession->session_token]);

                // Continuar con la sesión recién creada
                $sessionToken = $posSession->session_token;
            } else {
                // Admin u otros roles sin sesión → redirigir a pantalla PIN
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No hay sesión POS activa',
                        'redirect' => route('pos.login'),
                    ], 401);
                }

                return redirect()->route('pos.login')
                    ->with('error', 'Ingrese su PIN para usar el POS');
            }
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
