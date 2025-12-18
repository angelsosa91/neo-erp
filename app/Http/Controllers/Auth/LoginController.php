<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();

                // Registrar intento fallido (cuenta desactivada)
                LoginLog::logFailure($credentials['email'], $request, 'Account disabled');

                return back()->withErrors([
                    'email' => 'Su cuenta está desactivada.',
                ]);
            }

            // Registrar login exitoso
            LoginLog::logSuccess($user, $request);

            $request->session()->regenerate();

            // Redirigir según el rol del usuario
            return redirect()->intended($this->redirectPath($user));
        }

        // Registrar intento fallido (credenciales inválidas)
        LoginLog::logFailure($credentials['email'], $request, 'Invalid credentials');

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Determinar la ruta de redirección según el rol del usuario
     */
    protected function redirectPath($user): string
    {
        // VENDEDOR → POS directo (sin pantalla de PIN)
        // Ya están autenticados con email/password, van directo al POS
        if ($user->hasRole('vendedor')) {
            return '/pos';
        }

        // Todos los demás (Admin, Super Admin, otros roles) → Dashboard
        return '/dashboard';
    }
}
