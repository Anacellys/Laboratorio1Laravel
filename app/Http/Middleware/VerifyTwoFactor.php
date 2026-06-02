<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar si un usuario tiene 2FA pendiente durante el login
 *
 * Verifica si existe una sesión temporal de 2FA y solo permite acceder a:
 * - Rutas de verificación 2FA
 * - Logout
 *
 * Si intenta acceder a otras rutas, redirige a la pantalla de verificación
 */
class VerifyTwoFactor
{
    /**
     * Rutas que están permitidas durante la verificación de 2FA
     */
    private array $allowedRoutes = [
        'two-factor.verify',
        'two-factor.validate-code',
        'logout',
        'password.email',
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si no existe sesión 2FA temporal, permitir acceso normal
        if (!session()->has('2fa_user_id')) {
            return $next($request);
        }

        // Si existe sesión 2FA, solo permitir acceso a rutas específicas
        $routeName = $request->route()?->getName();

        // Permitir acceso a rutas específicas
        if (in_array($routeName, $this->allowedRoutes)) {
            return $next($request);
        }

        // Permitir requests AJAX de verificación
        if ($request->ajax() || $request->expectsJson()) {
            if (str_contains($routeName, 'two-factor')) {
                return $next($request);
            }
        }

        // Si intenta acceder a otra ruta, redirigir a verificación 2FA
        return redirect()->route('two-factor.verify')
            ->with('warning', 'Por favor completa la verificación de dos factores.');
    }
}
