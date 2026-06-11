<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Despues de registrar, el usuario escanea el QR 2FA.
     */
    protected $redirectTo = '/two-factor/setup';

    public function __construct(private readonly AuditLogService $auditLog)
    {
        $this->middleware('guest');
    }

    /**
     * Valida los campos del formulario solicitado.
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'string', 'email', 'max:255', 'unique:usuarios,correo'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'sexo' => ['required', 'in:Masculino,Femenino,Otro'],
        ], [
            'correo.unique' => 'Este correo ya esta registrado.',
            'password.confirmed' => 'Las contrasenas no coinciden.',
        ]);
    }

    /**
     * Crea el usuario, hashea la contrasena con password_hash y genera secret_2fa.
     */
    protected function create(array $data): User
    {
        $user = User::create([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'correo' => $data['correo'],
            'HashMagic' => password_hash($data['password'], PASSWORD_DEFAULT),
            'sexo' => $data['sexo'],
            'secret_2fa' => (new \Sonata\GoogleAuthenticator\GoogleAuthenticator())->generateSecret(),
        ]);

        $this->auditLog->logUserRegistration([
            'name' => $user->name,
            'email' => $user->correo,
        ]);

        return $user;
    }

    /**
     * Redirige al QR despues del registro.
     */
    protected function registered(Request $request, $user)
    {
        return redirect()->route('two-factor.setup')
            ->with('success', 'Registro exitoso. Escanea el QR para activar tu segundo factor.');
    }
}
