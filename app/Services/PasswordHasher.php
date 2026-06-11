<?php

namespace App\Services;

use App\Contracts\HashableInterface;

class PasswordHasher implements HashableInterface
{
    /**
     * Número de rondas de bcrypt.
     * El valor 12 es el estándar recomendado para producción.
     */
    private int $rounds;

    public function __construct(int $rounds = 12)
    {
        $this->rounds = $rounds;
    }

    public function generateHash(string $value): string
    {
        $hash = password_hash($value, PASSWORD_BCRYPT, ['cost' => $this->rounds]);

        if ($hash === false) {
            throw new \RuntimeException('No se pudo generar el hash de la contraseña.');
        }

        return $hash;
    }


    public function validateHash(string $value, string $hash): bool
    {
        return password_verify($value, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => $this->rounds]);
    }
}