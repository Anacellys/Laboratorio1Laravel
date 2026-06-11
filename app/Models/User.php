<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

#[Hidden(['HashMagic', 'remember_token', 'secret_2fa'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * La tabla y llave primaria usan los nombres solicitados por el enunciado.
     */
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';

    /**
     * Campos que se pueden asignar masivamente desde el registro.
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'correo',
        'HashMagic',
        'sexo',
        'secret_2fa',
        'two_factor_enabled',
        'two_factor_confirmed_at',
    ];

    /**
     * Laravel debe leer HashMagic como columna real de contrasena.
     */
    public function getAuthPasswordName(): string
    {
        return 'HashMagic';
    }

    /**
     * Compatibilidad con el proveedor de autenticacion de Laravel.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->HashMagic;
    }

    /**
     * Muestra un nombre legible en la barra de navegacion.
     */
    public function getNameAttribute(): string
    {
        return trim($this->nombre . ' ' . $this->apellido);
    }

    /**
     * Expone email como alias de correo para componentes Laravel existentes.
     */
    public function getEmailAttribute(): string
    {
        return (string) $this->correo;
    }

    /**
     * Casts de fechas y banderas.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Genera y guarda un secret Base32 compatible con Google Authenticator.
     */
    public function generateTwoFactorSecret(): string
    {
        $secret = (new GoogleAuthenticator())->generateSecret();

        $this->secret_2fa = $secret;
        $this->save();

        return $secret;
    }

    /**
     * Crea la URL de QR con GoogleQrUrl de Sonata.
     */
    public function getTwoFactorQrCodeUrl(): string
    {
        if (!$this->secret_2fa) {
            $this->generateTwoFactorSecret();
        }

        return GoogleQrUrl::generate($this->correo, $this->secret_2fa, config('app.name', 'AutenticacionLab'), 250);
    }

    /**
     * Valida el codigo temporal de seis digitos contra el secret guardado.
     */
    public function validateTwoFactorCode(string $code, int $discrepancy = 1): bool
    {
        if (!$this->secret_2fa) {
            return false;
        }

        return (new GoogleAuthenticator())->checkCode($this->secret_2fa, $code, $discrepancy);
    }

    /**
     * Indica si el usuario ya confirmo 2FA.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return (bool) $this->two_factor_enabled;
    }

    /**
     * Limpia la configuracion 2FA del usuario.
     */
    public function disableTwoFactor(): void
    {
        $this->two_factor_enabled = false;
        $this->secret_2fa = $this->generateTwoFactorSecret();
        $this->two_factor_confirmed_at = null;
        $this->save();
    }
}
