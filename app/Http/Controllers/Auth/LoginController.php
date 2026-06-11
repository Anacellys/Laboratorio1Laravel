<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Ruta final cuando usuario, contrasena y 2FA son correctos.
     */
    protected $redirectTo = '/home';

    public function __construct(private readonly AuditLogService $auditLog)
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Laravel usara el campo correo como identificador principal.
     */
    public function username(): string
    {
        return 'correo';
    }

    /**
     * Reglas de validacion del primer paso de login.
     */
    protected function validateLogin(Request $request): void
    {
        $request->validate([
            'correo' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
    }

    /**
     * Valida correo y contrasena; si son correctos exige la pantalla 2FA.
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        $user = User::where('correo', $request->correo)->first();

        if (!$user || !password_verify($request->password, $user->HashMagic)) {
            $this->auditLog->logLoginFailed($request->correo, 'invalid_credentials');
            $this->incrementLoginAttempts($request);

            return $this->sendFailedLoginResponse($request);
        }

        session()->put('2fa_user_id', $user->getKey());
        session()->put('2fa_remember', (bool) $request->boolean('remember'));

        $this->auditLog->log(
            AuditLogService::EVENT_LOGIN_SUCCESS,
            ['status' => 'password_ok_pending_2fa', 'email' => $user->correo],
            (string) $user->getKey()
        );

        return redirect()->route('two-factor.verify')
            ->with('info', 'Ingresa el codigo de Google Authenticator para completar el acceso.');
    }

    /**
     * Mensaje cuando falla correo o contrasena.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        return redirect()->back()
            ->withInput($request->only('correo', 'remember'))
            ->withErrors([
                'correo' => trans('auth.failed'),
            ]);
    }

    /**
     * Registra cierre de sesion en el log de auditoria.
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            $this->auditLog->logLogout(Auth::id());
        }

        return $this->traitLogout($request);
    }

    /**
     * Alias para invocar el logout del trait sin perder auditoria.
     */
    private function traitLogout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
