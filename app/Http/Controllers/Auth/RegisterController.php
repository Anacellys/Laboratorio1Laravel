<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/two-factor/setup';

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
        $this->middleware('guest');
        $this->auditLog = $auditLog;
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'El nombre es requerido.',
            'email.required' => 'El correo es requerido.',
            'email.email' => 'El correo debe ser válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es requerida.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * Genera automáticamente un secret 2FA para el nuevo usuario
     *
     * @return User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Generar secret 2FA automáticamente
        $user->generateTwoFactorSecret();

        // Registrar en logs
        $this->auditLog->logUserRegistration([
            'name' => $user->name,
            'email' => $user->email,
        ]);

        return $user;
    }

    /**
     * The user has been registered.
     *
     * Redirige a setup de 2FA después del registro
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        // Redirigir a setup de 2FA en lugar de a home
        return redirect()->route('two-factor.setup')
            ->with('success', 'Registro exitoso. Ahora configura tu autenticación de dos factores.');
    }
}

