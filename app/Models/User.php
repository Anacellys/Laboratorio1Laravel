<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PragmaRX\Google2FA\Google2FA;
use OTPHP\TOTP;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

#[Fillable(['name', 'email', 'password', 'two_factor_secret', 'two_factor_enabled'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Genera un nuevo secret TOTP para 2FA
     *
     * @return string Secret en formato Base32
     */
    public function generateTwoFactorSecret(): string
    {
        $totp = TOTP::create();
        $secret = $totp->getSecret();

        // Guardar el secret en la base de datos
        $this->two_factor_secret = $secret;
        $this->save();

        return $secret;
    }

    /**
     * Obtiene la URL de Google Authenticator con el QR code
     *
     * @return string URL para generar el QR code en data URI
     */
    public function getTwoFactorQrCodeUrl(): string
    {
        if (!$this->two_factor_secret) {
            $this->generateTwoFactorSecret();
        }

        // Construir URL para Google Authenticator
        $totp = TOTP::create($this->two_factor_secret);
        $totp->setLabel($this->email);
        $totp->setIssuer(config('app.name'));

        // Obtener la URL otpauth://
        $qrUrl = $totp->getProvisioningUri();

        // Generar QR code usando endroid/qr-code
        $qrCode = QrCode::create($qrUrl)
            ->setSize(300)
            ->setMargin(10);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        // Retornar como data URI
        return 'data:image/png;base64,' . base64_encode($result->getString());
    }

    /**
     * Valida un código TOTP ingresado por el usuario
     *
     * @param string $code Código de 6 dígitos ingresado por el usuario
     * @param int $discrepancy Margen de tiempo permitido (ventanas de 30 segundos)
     * @return bool True si el código es válido
     */
    public function validateTwoFactorCode(string $code, int $discrepancy = 1): bool
    {
        if (!$this->two_factor_secret || !$this->two_factor_enabled) {
            return false;
        }

        try {
            $totp = TOTP::create($this->two_factor_secret);

            // Verificar si el código es válido dentro del margen de tiempo
            return $totp->verify($code, time(), $discrepancy);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Habilita 2FA para el usuario después de validar el código
     *
     * @param string $code Código TOTP a validar
     * @return bool True si se habilitó correctamente
     */
    public function enableTwoFactor(string $code): bool
    {
        if ($this->validateTwoFactorCode($code)) {
            $this->two_factor_enabled = true;
            $this->two_factor_confirmed_at = now();
            $this->save();

            return true;
        }

        return false;
    }

    /**
     * Deshabilita 2FA para el usuario
     *
     * @return void
     */
    public function disableTwoFactor(): void
    {
        $this->two_factor_enabled = false;
        $this->two_factor_secret = null;
        $this->two_factor_confirmed_at = null;
        $this->save();
    }

    /**
     * Verifica si el usuario tiene 2FA habilitado
     *
     * @return bool
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled ?? false;
    }
}


