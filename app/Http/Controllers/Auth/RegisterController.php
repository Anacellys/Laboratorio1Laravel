<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\HashableInterface;
use App\Http\Controllers\Controller;
use App\Http\Sanitizers\InputSanitizer;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;


    protected $redirectTo = '/two-factor/setup';

    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly HashableInterface $hasher,
    ) {
        $this->middleware('guest');
    }

    
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'nombre'   => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'correo'   => ['required', 'string', 'email', 'max:255', 'unique:usuarios,correo'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'sexo'     => ['required', 'in:Masculino,Femenino,Otro'],
        ], [
            'correo.unique'      => 'Este correo ya esta registrado.',
            'password.confirmed' => 'Las contrasenas no coinciden.',
        ]);
    }

    
    private function sanitizeData(array $data): array
    {
        return [
            'nombre'   => InputSanitizer::sanitizeName($data['nombre']),
            'apellido' => InputSanitizer::sanitizeName($data['apellido']),
            'correo'   => InputSanitizer::sanitizeEmail($data['correo']),
            'password' => $data['password'], 
            'sexo'     => InputSanitizer::sanitizeString($data['sexo']),
        ];
    }

   
    protected function create(array $data): User
    {
        $clean = $this->sanitizeData($data);

        $hash = $this->hasher->generateHash($clean['password']);

        $user = User::create([
            'nombre'    => $clean['nombre'],
            'apellido'  => $clean['apellido'],
            'correo'    => $clean['correo'],
            'HashMagic' => $hash,
            'sexo'      => $clean['sexo'],
            'secret_2fa' => (new \Sonata\GoogleAuthenticator\GoogleAuthenticator())->generateSecret(),
        ]);

        $this->auditLog->logUserRegistration([
            'name'  => $user->name,
            'email' => $user->correo,
        ]);

        return $user;
    }

    protected function registered(Request $request, $user)
    {
        return redirect()->route('two-factor.setup')
            ->with('success', 'Registro exitoso. Escanea el QR para activar tu segundo factor.');
    }
}