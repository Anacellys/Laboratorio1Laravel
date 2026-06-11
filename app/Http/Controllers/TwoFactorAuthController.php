<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controlador del segundo factor de autenticacion.
 */
class TwoFactorAuthController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
        //
    }

    /**
     * Muestra el QR generado con GoogleQrUrl despues del registro.
     */
    public function setup(): View
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->secret_2fa) {
            $user->generateTwoFactorSecret();
        }

        $this->auditLog->logTwoFactorSetup($user->getKey());

        return view('auth.two-factor.setup', [
            'qrCode' => $user->getTwoFactorQrCodeUrl(),
            'secret' => $user->secret_2fa,
            'user' => $user,
        ]);
    }

    /**
     * Confirma el secret 2FA escaneado por el usuario.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!$user->validateTwoFactorCode($request->code)) {
            $this->auditLog->logTwoFactorFailed($user->getKey(), 'invalid_code_during_setup');

            return back()
                ->withErrors(['code' => 'Codigo 2FA incorrecto. Intenta nuevamente.'])
                ->withInput();
        }

        $user->two_factor_enabled = true;
        $user->two_factor_confirmed_at = now();
        $user->save();

        $this->auditLog->logTwoFactorVerified($user->getKey());

        return redirect()->route('home')
            ->with('success', '2FA habilitado correctamente.');
    }

    /**
     * Segunda pantalla del login para ingresar el codigo temporal.
     */
    public function verify(): View|RedirectResponse
    {
        if (!session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor.verify');
    }

    /**
     * Valida el codigo temporal; solo aqui se completa la sesion final.
     */
    public function validateCode(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if (!session()->has('2fa_user_id')) {
            return redirect()->route('login')
                ->withErrors(['correo' => 'Sesion expirada. Inicia sesion nuevamente.']);
        }

        $user = User::find(session()->get('2fa_user_id'));

        if (!$user) {
            session()->forget(['2fa_user_id', '2fa_remember']);

            return redirect()->route('login')
                ->withErrors(['correo' => 'Usuario no encontrado.']);
        }

        if (!$user->hasTwoFactorEnabled() || !$user->validateTwoFactorCode($request->code)) {
            $this->auditLog->logTwoFactorFailed($user->getKey(), 'invalid_code_during_login');

            return back()
                ->withErrors(['code' => 'Codigo 2FA incorrecto. Acceso denegado.'])
                ->withInput();
        }

        Auth::login($user, (bool) session()->get('2fa_remember', false));
        session()->forget(['2fa_user_id', '2fa_remember']);
        session()->regenerate();

        $this->auditLog->logLoginSuccess($user->correo, $user->getKey());

        return redirect()->intended(route('home'))
            ->with('success', 'Has iniciado sesion exitosamente.');
    }

    /**
     * Permite reiniciar el secret 2FA despues de confirmar la contrasena.
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!password_verify($request->password, $user->HashMagic)) {
            return back()->withErrors(['password' => 'La contrasena es incorrecta.']);
        }

        $user->disableTwoFactor();
        $this->auditLog->logTwoFactorDisabled($user->getKey());

        return back()->with('success', '2FA fue deshabilitado y se genero un nuevo secret.');
    }
}
