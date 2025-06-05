<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller
{
    /**
     * Mostrar el formulario de inicio de sesión.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Procesar el intento de inicio de sesión.
     * Valida credenciales, estado y rol.
     * Autentica al usuario y redirige según accesos y rol.
     */
    public function store(Request $request)
    {
        Log::info('Intento de login', ['rfc' => $request->rfc]);

        // Validación básica de datos
        $request->validate([
            'rfc' => ['required', 'string', 'max:13'],
            'password' => [
                'required', 'string', 'min:8',
                'regex:/[A-Z]/', 'regex:/[a-z]/',
                'regex:/[0-9]/', 'regex:/[\W]/',
            ],
        ]);
        Log::info('Validación pasada');

        // Si ya hay sesión activa, cerrar sesión previa
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        Log::info('Sesión anterior cerrada (si existía)');

        // Buscar anfitrión por RFC
        $anfitrion = \App\Models\Anfitrion::where('RFC', $request->rfc)->first();
        if (!$anfitrion) {
            Log::warning('RFC no encontrado');
            return back()->withErrors(['rfc' => 'Las credenciales son incorrectas.']);
        }

        // Verificar contraseña
        if (!\Illuminate\Support\Facades\Hash::check($request->password, $anfitrion->password)) {
            Log::warning('Contraseña incorrecta');
            return back()->withErrors(['rfc' => 'Las credenciales son incorrectas.']);
        }
        Log::info('Contraseña correcta');

        // Verificar que usuario esté activo
        if (!$anfitrion->activo) {
            Log::warning('Usuario inactivo');
            return back()->withErrors(['rfc' => 'Este usuario está inactivo.']);
        }

        // Validar rol permitido
        if (!in_array($anfitrion->rol, ['master', 'administrador', 'recepcionista', 'anfitrion'])) {
            Log::warning('Rol inválido', ['rol' => $anfitrion->rol]);
            return back()->withErrors(['rfc' => 'Acceso denegado. Rol inválido.']);
        }

        // Autenticar usuario
        Log::info('Login ejecutando Auth::login()');
        Auth::login($anfitrion, $request->boolean('remember'));
        $request->session()->regenerate();

        Log::info('Usuario autenticado con éxito', [
            'RFC' => $anfitrion->RFC,
            'rol' => $anfitrion->rol,
            'spa_id' => $anfitrion->spa_id,
            'accesos' => $anfitrion->accesos,
        ]);

        // Redirigir según accesos y rol
        return $this->redirectUser($anfitrion);
    }

    /**
     * Redirige al usuario tras login según su rol, departamento y accesos.
     */
    private function redirectUser($user)
    {
        $spaNombre = strtolower(optional($user->spa)->nombre);

        Log::info('Iniciando redirección para usuario:', [
            'rfc' => $user->RFC,
            'rol' => $user->rol,
            'spa_principal' => $spaNombre,
            'accesos' => $user->accesos,
            'departamento' => $user->departamento,
        ]);

        // Decodificar accesos JSON si es necesario
        $accesos = is_array($user->accesos) ? $user->accesos : json_decode($user->accesos, true) ?? [];

        // Spas extras diferentes al principal
        $spaExtras = array_filter($accesos, fn ($id) => $id != $user->spa_id);
        $tieneMultiplesSpas = count($spaExtras) > 0;

        // Usuarios master o con múltiples spas van a vista de módulos
        if ($user->rol === 'master' || $tieneMultiplesSpas) {
            return redirect()->route('modulos');
        }

        // Guardar spa actual en sesión si no está definido
        if ($spaNombre && !session()->has('current_spa')) {
            session(['current_spa' => $spaNombre]);
            session(['current_spa_id' => $user->spa_id]);
        }

        // Redirección según departamento del usuario
        return match ($user->departamento) {
            'spa', 'global' => redirect()->route('reservations.index'),
            'salon de belleza' => redirect()->route('salon.index'),
            default => redirect()->route('reservations.index'),
        };
    }

    /**
     * Cerrar sesión del usuario y limpiar sesión.
     */
    public function destroy(Request $request)
    {
        $usuario = Auth::user();

        Log::info('Usuario cerrando sesión', [
            'rfc' => $usuario?->RFC,
            'rol' => $usuario?->rol,
            'spa' => session('current_spa'),
        ]);

        Auth::guard('web')->logout();

        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesión cerrada correctamente');
    }
}
