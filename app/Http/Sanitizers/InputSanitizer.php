<?php

namespace App\Http\Sanitizers;

/**
 * Clase de sanitización de entradas del usuario.
 *
 * Todos los métodos son estáticos para ser invocados directamente
 * sin necesidad de instanciar la clase, tal como lo requiere el laboratorio.
 *
 * Uso en RegisterController:
 *   $nombre  = InputSanitizer::sanitizeString($data['nombre']);
 *   $correo  = InputSanitizer::sanitizeEmail($data['correo']);
 *   $html    = InputSanitizer::sanitizeHtml($data['descripcion']);
 *   $entero  = InputSanitizer::sanitizeInteger($data['edad']);
 *   $url     = InputSanitizer::sanitizeUrl($data['sitio']);
 */
class InputSanitizer
{
    
    public static function sanitizeString(string $value): string
    {
        $value = trim(strip_tags($value));

        // Convertir caracteres especiales a entidades HTML (previene XSS)
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Eliminar null bytes y caracteres de control
        $value = str_replace(["\0", "\x00", "\x1A"], '', $value);

        return $value;
    }

    public static function sanitizeEmail(string $value): string
    {
        // Eliminar espacios y pasar a minúsculas
        $value = strtolower(trim($value));

        // Eliminar caracteres no permitidos en emails
        $value = (string) filter_var($value, FILTER_SANITIZE_EMAIL);

        return $value;
    }

   
    public static function sanitizeHtml(
        string $value,
        string $allowedTags = '<p><br><strong><em><ul><ol><li>'
    ): string {

        $value = strip_tags(trim($value), $allowedTags);

        $value = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $value);
        $value = preg_replace('/javascript\s*:/i', '', $value);
        $value = preg_replace('/data\s*:/i', '', $value);

        return $value;
    }

    
    public static function sanitizeInteger(mixed $value): int
    {
        $cleaned = preg_replace('/[^\d\-]/', '', (string) $value);

        return is_numeric($cleaned) ? (int) $cleaned : 0;
    }

    
    public static function sanitizeUrl(string $value): string
    {
        $value = trim($value);

        // Sanitizar caracteres no permitidos en URLs
        $value = (string) filter_var($value, FILTER_SANITIZE_URL);

        // Solo permitir http y https (bloquea javascript:, ftp:, data:, etc.)
        if (!preg_match('/^https?:\/\//i', $value)) {
            return '';
        }

        return $value;
    }

    
    public static function sanitizeName(string $value, int $maxLen = 100): string
    {
        $value = trim($value);

        // Eliminar caracteres no permitidos en nombres (solo letras, espacios, tildes, ñ, guiones)
        $value = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\-]/u', '', $value);

        // Eliminar espacios dobles
        $value = preg_replace('/\s+/', ' ', $value);

        // Capitalizar cada palabra
        $value = mb_convert_case(trim($value), MB_CASE_TITLE, 'UTF-8');

        // Limitar longitud
        return mb_substr($value, 0, $maxLen, 'UTF-8');
    }
}