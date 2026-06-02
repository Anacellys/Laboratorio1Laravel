<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Servicio para registrar y gestionar logs de auditoría
 *
 * Registra eventos de:
 * - Registro de usuarios
 * - Inicio de sesión
 * - Errores de autenticación
 * - Errores de 2FA
 * - Acciones de seguridad
 */
class AuditLogService
{
    // Tipos de eventos que se pueden registrar
    const EVENT_USER_REGISTERED = 'user_registered';
    const EVENT_LOGIN_SUCCESS = 'login_success';
    const EVENT_LOGIN_FAILED = 'login_failed';
    const EVENT_2FA_SETUP = 'two_factor_setup';
    const EVENT_2FA_VERIFIED = 'two_factor_verified';
    const EVENT_2FA_FAILED = 'two_factor_failed';
    const EVENT_2FA_DISABLED = 'two_factor_disabled';
    const EVENT_PASSWORD_CHANGED = 'password_changed';
    const EVENT_LOGOUT = 'logout';

    /**
     * Canal de log para auditoría
     */
    private string $channel = 'audit';

    /**
     * Ruta del archivo de logs personalizados
     */
    private string $logPath;

    public function __construct()
    {
        $this->logPath = storage_path('logs/audit.log');
    }

    /**
     * Registra un evento en el log de auditoría
     *
     * @param string $eventType Tipo de evento (usar constantes de la clase)
     * @param array $data Datos adicionales del evento
     * @param string $userId ID del usuario (opcional)
     * @param string $ipAddress Dirección IP del cliente
     * @return void
     */
    public function log(
        string $eventType,
        array $data = [],
        ?string $userId = null,
        ?string $ipAddress = null
    ): void {
        // Obtener IP del cliente si no se proporciona
        if (!$ipAddress) {
            $ipAddress = request()->ip() ?? 'unknown';
        }

        // Obtener ID del usuario autenticado si no se proporciona
        if (!$userId && auth()->check()) {
            $userId = auth()->user()->id;
        }

        // Construir mensaje de log estructurado
        $logData = [
            'event_type' => $eventType,
            'timestamp' => Carbon::now()->toIso8601String(),
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => request()->userAgent() ?? 'unknown',
            'url' => request()->url() ?? 'unknown',
            'method' => request()->method() ?? 'unknown',
            'additional_data' => $data,
        ];

        // Escribir en el archivo de log personalizado
        $this->writeToFile($logData);

        // También registrar en el log de Laravel para debugging
        Log::channel($this->channel)->info("[$eventType]", $logData);
    }

    /**
     * Registra un evento de registro de usuario
     *
     * @param array $userData Datos del usuario registrado
     * @return void
     */
    public function logUserRegistration(array $userData): void
    {
        $this->log(
            self::EVENT_USER_REGISTERED,
            [
                'email' => $userData['email'] ?? null,
                'name' => $userData['name'] ?? null,
            ]
        );
    }

    /**
     * Registra un intento de login exitoso
     *
     * @param string $email Email del usuario
     * @param int $userId ID del usuario
     * @return void
     */
    public function logLoginSuccess(string $email, int $userId): void
    {
        $this->log(
            self::EVENT_LOGIN_SUCCESS,
            ['email' => $email],
            (string)$userId
        );
    }

    /**
     * Registra un intento de login fallido
     *
     * @param string $email Email del usuario que intentó login
     * @param string $reason Razón del fallo (credenciales inválidas, usuario no existe, etc)
     * @return void
     */
    public function logLoginFailed(string $email, string $reason = 'invalid_credentials'): void
    {
        $this->log(
            self::EVENT_LOGIN_FAILED,
            [
                'email' => $email,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Registra la configuración de 2FA
     *
     * @param int $userId ID del usuario
     * @return void
     */
    public function logTwoFactorSetup(int $userId): void
    {
        $this->log(
            self::EVENT_2FA_SETUP,
            [],
            (string)$userId
        );
    }

    /**
     * Registra una verificación exitosa de 2FA
     *
     * @param int $userId ID del usuario
     * @return void
     */
    public function logTwoFactorVerified(int $userId): void
    {
        $this->log(
            self::EVENT_2FA_VERIFIED,
            [],
            (string)$userId
        );
    }

    /**
     * Registra un intento fallido de verificación de 2FA
     *
     * @param int $userId ID del usuario
     * @param string $reason Razón del fallo
     * @return void
     */
    public function logTwoFactorFailed(int $userId, string $reason = 'invalid_code'): void
    {
        $this->log(
            self::EVENT_2FA_FAILED,
            ['reason' => $reason],
            (string)$userId
        );
    }

    /**
     * Registra la deshabilitación de 2FA
     *
     * @param int $userId ID del usuario
     * @return void
     */
    public function logTwoFactorDisabled(int $userId): void
    {
        $this->log(
            self::EVENT_2FA_DISABLED,
            [],
            (string)$userId
        );
    }

    /**
     * Registra un logout
     *
     * @param int $userId ID del usuario
     * @return void
     */
    public function logLogout(int $userId): void
    {
        $this->log(
            self::EVENT_LOGOUT,
            [],
            (string)$userId
        );
    }

    /**
     * Escribe el evento en el archivo de log personalizado
     *
     * @param array $logData Datos del evento
     * @return void
     */
    private function writeToFile(array $logData): void
    {
        try {
            // Crear el directorio si no existe
            if (!file_exists(dirname($this->logPath))) {
                mkdir(dirname($this->logPath), 0755, true);
            }

            // Convertir datos a JSON
            $jsonLog = json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // Agregar timestamp readable y separador
            $logLine = sprintf(
                "[%s] %s\n%s\n%s\n\n",
                $logData['timestamp'],
                str_repeat('-', 80),
                $jsonLog,
                str_repeat('=', 80)
            );

            // Escribir en el archivo (append)
            file_put_contents($this->logPath, $logLine, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            // Si falla la escritura del archivo, al menos registrar en el log de Laravel
            Log::error('Error writing to audit log file: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene los últimos eventos del archivo de log
     *
     * @param int $lines Número de líneas a leer (default 100)
     * @return array Últimos eventos
     */
    public function getRecentEvents(int $lines = 100): array
    {
        try {
            if (!file_exists($this->logPath)) {
                return [];
            }

            $file = new \SplFileObject($this->logPath, 'r');
            $file->seek(PHP_INT_MAX);
            $lastLine = $file->key();

            $events = [];
            $start = max(0, $lastLine - ($lines * 11)); // ~11 líneas por evento

            $file->seek($start);
            foreach ($file as $line) {
                // Buscar líneas que comienzan con [timestamp]
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.*?)\]/', $line, $matches)) {
                    if (!empty($events)) {
                        // Este es el comienzo de un nuevo evento
                    }
                }
            }

            return $events;
        } catch (\Exception $e) {
            Log::error('Error reading audit log: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Limpia el archivo de log (útil para testing)
     *
     * @return void
     */
    public function clearLog(): void
    {
        try {
            if (file_exists($this->logPath)) {
                unlink($this->logPath);
            }
        } catch (\Exception $e) {
            Log::error('Error clearing audit log: ' . $e->getMessage());
        }
    }
}
