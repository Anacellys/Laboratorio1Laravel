<?php

namespace App\Contracts;

/**
 * Interfaz que define el contrato para la generación y validación de hashes.
 *
 * Cualquier clase que maneje el hasheo de contraseñas o datos sensibles
 * debe implementar esta interfaz para garantizar consistencia en el sistema.
 */
interface HashableInterface
{
    /**
     * Genera un hash seguro a partir de un valor en texto plano.
     *
     * @param  string $value  El valor en texto plano a hashear (ej: contraseña).
     * @return string         El hash generado.
     */
    public function generateHash(string $value): string;

    /**
     * Valida si un valor en texto plano corresponde a un hash almacenado.
     *
     * @param  string $value  El valor en texto plano a verificar.
     * @param  string $hash   El hash almacenado contra el que se compara.
     * @return bool           True si el valor coincide con el hash, false si no.
     */
    public function validateHash(string $value, string $hash): bool;

    /**
     * Indica si un hash existente necesita ser regenerado.
     *
     * Útil para detectar hashes generados con parámetros antiguos
     * (ej: menos rondas de bcrypt) y actualizarlos automáticamente.
     *
     * @param  string $hash   El hash almacenado a evaluar.
     * @return bool           True si el hash debe regenerarse, false si está vigente.
     */
    public function needsRehash(string $hash): bool;
}