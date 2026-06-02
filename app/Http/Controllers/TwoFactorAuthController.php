<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use OTPHP\TOTP;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Controlador para gestionar la autenticación de dos factores (2FA)
 *
 * Maneja:
 * - Generación de secrets TOTP
 * - Visualización de códigos QR
 * - Confirmación de 2FA
 * - Verificación de códigos durante login
 */
class TwoFactorAuthController extends Controller
{
    protected AuditLogService $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    /**
     * Muestra la pantalla de configuración de 2FA con QR code
     *
     * GET /two-factor/setup
     *
     * @return View Vista con QR code y secret
     */
    public function setup(): View
    {
        $user = Auth::user();

        // Si el usuario ya tiene 2FA habilitado, redirigir a home
        if ($user->hasTwoFactorEnabled()) {
            return view('auth.two-factor.already-enabled');
        }

        // Generar nuevo secret si no existe
        if (!$user->two_factor_secret) {
            $user->generateTwoFactorSecret();
        }

        // Obtener URL del QR code
        $qrCode = $user->getTwoFactorQrCodeUrl();

        // Crear TOTP para obtener el secret legible
        $totp = TOTP::create($user->two_factor_secret);
        $secret = $user->two_factor_secret;

        // Registrar en logs
        $this->auditLog->logTwoFactorSetup($user->id);

        return view('auth.two-factor.setup', [
            'qrCode' => $qrCode,
            'secret' => $secret,
            'user' => $user,
        ]);
    }

    /**
     * Confirma y guarda la configuración de 2FA
     *
     * POST /two-factor/confirm
     *
     * @param Request $request Debe contener 'code' (código TOTP de 6 dígitos)
     * @return RedirectResponse Redirige a home si es correcto, de vuelta a setup si falla
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|digits:6',
        ], [
            'code.required' => 'El código es requerido',
            'code.digits' => 'El código debe tener exactamente 6 dígitos',
        ]);

        $user = Auth::user();

        // Validar el código TOTP
        if (!$user->validateTwoFactorCode($request->code)) {
            $this->auditLog->logTwoFactorFailed($user->id, 'invalid_code_during_setup');

            return back()
                ->withErrors(['code' => 'El código ingresado es inválido. Intenta nuevamente.'])
                ->withInput();
        }

        // Habilitar 2FA
        $user->two_factor_enabled = true;
        $user->two_factor_confirmed_at = now();
        $user->save();

        $this->auditLog->logTwoFactorVerified($user->id);

        return redirect()->route('home')
            ->with('success', '¡Autenticación de dos factores habilitada exitosamente!');
    }

    /**
     * Muestra la pantalla de verificación de 2FA durante el login
     *
     * GET /two-factor/verify
     *
     * @return View|RedirectResponse Vista de verificación o redirige a login si no hay sesión temporal
     */
    public function verify(): View|RedirectResponse
    {
        // Verificar que existe una sesión temporal de 2FA
        if (!session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor.verify');
    }

    /**
     * Valida el código TOTP durante el login
     *
     * POST /two-factor/verify
     *
     * @param Request $request Debe contener 'code' (código TOTP de 6 dígitos)
     * @return RedirectResponse Redirige a home si es correcto, de vuelta a verify si falla
     */
    public function validateCode(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|digits:6',
        ], [
            'code.required' => 'El código es requerido',
            'code.digits' => 'El código debe tener exactamente 6 dígitos',
        ]);

        // Verificar sesión temporal
        if (!session()->has('2fa_user_id')) {
            return redirect()->route('login')->withErrors(['session' => 'Sesión expirada. Inicia sesión nuevamente.']);
        }

        $userId = session()->get('2fa_user_id');
        $user = User::find($userId);

        if (!$user) {
            session()->forget('2fa_user_id');
            return redirect()->route('login')->withErrors(['user' => 'Usuario no encontrado.']);
        }

        // Validar el código TOTP
        if (!$user->validateTwoFactorCode($request->code)) {
            $this->auditLog->logTwoFactorFailed($user->id, 'invalid_code_during_login');

            return back()
                ->withErrors(['code' => 'El código ingresado es inválido. Intenta nuevamente.'])
                ->withInput();
        }

        // Código válido, completar login
        Auth::login($user, $request->remember);

        // Limpiar sesión temporal
        session()->forget('2fa_user_id');

        $this->auditLog->logLoginSuccess($user->email, $user->id);

        return redirect()->intended(route('home'))
            ->with('success', 'Has iniciado sesión exitosamente.');
    }

    /**
     * Deshabilita 2FA para el usuario actual
     *
     * POST /two-factor/disable
     *
     * @param Request $request Debe contener 'password' para confirmar la identidad
     * @return RedirectResponse Redirige a settings o back
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string',
        ], [
            'password.required' => 'La contraseña es requerida para deshabilitar 2FA',
        ]);

        $user = Auth::user();

        // Verificar contraseña
        if (!\Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'La contraseña es incorrecta.']);
        }

        // Deshabilitar 2FA
        $user->disableTwoFactor();

        $this->auditLog->logTwoFactorDisabled($user->id);

        return back()->with('success', 'Autenticación de dos factores deshabilitada.');
    }
}
