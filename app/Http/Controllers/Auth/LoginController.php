<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Servicio de logs de auditoría
     */
    protected AuditLogService $auditLog;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(AuditLogService $auditLog)
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
        $this->auditLog = $auditLog;
    }

    /**
     * Handle a login request to the application.
     *
     * Intercepta el login para verificar 2FA
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // Si hay demasiados intentos de login fallidos, bloquear
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        // Obtener usuario por email
        $user = \App\Models\User::where('email', $request->email)->first();

        // Si no existe o contraseña es incorrecta
        if (!$user || !\Hash::check($request->password, $user->password)) {
            $this->auditLog->logLoginFailed(
                $request->email,
                'invalid_credentials'
            );

            $this->incrementLoginAttempts($request);
            return $this->sendFailedLoginResponse($request);
        }

        // Si el usuario tiene 2FA habilitado
        if ($user->hasTwoFactorEnabled()) {
            // Crear sesión temporal para 2FA
            session()->put('2fa_user_id', $user->id);
            session()->put('2fa_email', $user->email);

            $this->auditLog->log(
                AuditLogService::EVENT_LOGIN_SUCCESS,
                ['status' => 'pending_2fa'],
                (string)$user->id
            );

            // Redirigir a pantalla de verificación 2FA
            return redirect()->route('two-factor.verify')
                ->with('info', 'Por favor, ingresa tu código de autenticación de dos factores.');
        }

        // Si no tiene 2FA, completar login normal
        Auth::login($user, $request->remember);
        $this->clearLoginAttempts($request);

        $this->auditLog->logLoginSuccess($user->email, $user->id);

        return $this->sendLoginResponse($request);
    }

    /**
     * Handle a failed login attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors([
                $this->username() => trans('auth.failed'),
            ]);
    }
}

