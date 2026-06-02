/**
 * Script SQL completo para el Sistema de Autenticación con 2FA
 * Base de datos: laboratoriolaravel
 *
 * Este script crea la estructura completa de la base de datos
 * para el sistema de autenticación con doble factor.
 *
 * INSTRUCCIONES:
 * 1. Conectarse a MySQL como usuario con permisos
 * 2. Crear la base de datos: CREATE DATABASE laboratoriolaravel;
 * 3. Usar la base de datos: USE laboratoriolaravel;
 * 4. Ejecutar este script
 *
 * Estructura de tablas:
 * - users: Almacena datos de usuarios y secrets 2FA
 * - cache: Cache de Laravel
 * - jobs: Cola de trabajos
 * - job_batches: Lotes de trabajos
 * - sessions: Sesiones de usuarios
 * - password_reset_tokens: Tokens para reset de contraseña
 */

-- ====================================================================
-- TABLA: users
-- DESCRIPCION: Almacena información de usuarios y 2FA
-- ====================================================================
CREATE TABLE IF NOT EXISTS `users` (
    -- Identificadores y datos básicos
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL COMMENT 'Nombre completo del usuario',
    `email` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Correo electrónico único',
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de verificación de email',
    `password` VARCHAR(255) NOT NULL COMMENT 'Contraseña hasheada con bcrypt',

    -- Campos de 2FA (Autenticación de Dos Factores)
    `two_factor_secret` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Secret TOTP para Google Authenticator (Base32)',
    `two_factor_enabled` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Indica si 2FA está habilitado',
    `two_factor_confirmed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de confirmación de 2FA',

    -- Tokens y timestamps
    `remember_token` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Token para recordar login',
    `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de creación del usuario',
    `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de última actualización',

    -- Índices
    KEY `users_email_index` (`email`),
    KEY `users_two_factor_enabled_index` (`two_factor_enabled`),
    KEY `users_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabla de usuarios del sistema con soporte para 2FA';

-- ====================================================================
-- TABLA: cache
-- DESCRIPCION: Almacena datos en caché
-- ====================================================================
CREATE TABLE IF NOT EXISTS `cache` (
    `key` VARCHAR(255) NOT NULL UNIQUE PRIMARY KEY,
    `value` LONGTEXT NOT NULL,
    `expiration` INT NOT NULL,
    KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabla de caché de Laravel';

-- ====================================================================
-- TABLA: cache_locks
-- DESCRIPCION: Locks distribuidos para caché
-- ====================================================================
CREATE TABLE IF NOT EXISTS `cache_locks` (
    `key` VARCHAR(255) NOT NULL UNIQUE PRIMARY KEY,
    `owner` VARCHAR(255) NOT NULL,
    `expiration` INT NOT NULL,
    KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabla de locks de caché';

-- ====================================================================
-- TABLA: sessions
-- DESCRIPCION: Almacena sesiones de usuarios
-- ====================================================================
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` VARCHAR(255) NOT NULL UNIQUE PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL COMMENT 'Dirección IP del cliente',
    `user_agent` TEXT NULL DEFAULT NULL COMMENT 'User Agent del navegador',
    `payload` LONGTEXT NOT NULL COMMENT 'Datos de sesión',
    `last_activity` INT NOT NULL COMMENT 'Última actividad (timestamp)',
    KEY `sessions_user_id_index` (`user_id`),
    KEY `sessions_last_activity_index` (`last_activity`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabla de sesiones de Laravel';

-- ====================================================================
-- TABLA: jobs
-- DESCRIPCION: Cola de trabajos en background
-- ====================================================================
CREATE TABLE IF NOT EXISTS `jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `queue` VARCHAR(255) NOT NULL DEFAULT 'default',
    `payload` LONGTEXT NOT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `reserved_at` INT UNSIGNED NULL DEFAULT NULL,
    `available_at` INT UNSIGNED NOT NULL,
    `created_at` INT UNSIGNED NOT NULL,
    KEY `jobs_queue_index` (`queue`),
    KEY `jobs_reserved_at_index` (`reserved_at`),
    KEY `jobs_available_at_index` (`available_at`),
    KEY `jobs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabla de cola de trabajos';

-- ====================================================================
-- TABLA: job_batches
-- DESCRIPCION: Lotes de trabajos
-- ====================================================================
CREATE TABLE IF NOT EXISTS `job_batches` (
    `id` VARCHAR(255) NOT NULL UNIQUE PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `total_jobs` INT NOT NULL,
    `pending_jobs` INT NOT NULL,
    `failed_jobs` INT NOT NULL,
    `failed_job_ids` LONGTEXT NOT NULL,
    `options` MEDIUMTEXT NULL DEFAULT NULL,
    `cancelled_at` INT NULL DEFAULT NULL,
    `created_at` INT NOT NULL,
    `finished_at` INT NULL DEFAULT NULL,
    KEY `job_batches_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabla de lotes de trabajos';

-- ====================================================================
-- TABLA: password_reset_tokens
-- DESCRIPCION: Tokens para reset de contraseña
-- ====================================================================
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `email` VARCHAR(255) NOT NULL UNIQUE PRIMARY KEY,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    KEY `password_reset_tokens_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabla de tokens para reset de contraseña';

-- ====================================================================
-- TABLA: failed_jobs
-- DESCRIPCION: Trabajos que fallaron
-- ====================================================================
CREATE TABLE IF NOT EXISTS `failed_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(255) NOT NULL UNIQUE,
    `connection` TEXT NOT NULL,
    `queue` TEXT NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `exception` LONGTEXT NOT NULL,
    `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `failed_jobs_uuid_index` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabla de trabajos fallidos';

-- ====================================================================
-- USUARIOS DE PRUEBA (OPCIONAL)
-- ====================================================================

-- Crear usuario de prueba sin 2FA
INSERT INTO `users` (
    `name`,
    `email`,
    `password`,
    `email_verified_at`,
    `two_factor_enabled`,
    `created_at`,
    `updated_at`
) VALUES (
    'Usuario Prueba',
    'usuario@ejemplo.com',
    '$2y$12$k4I5nBlBqKChT8ViNXRGzOV8aILJKzFxGTvV7FLXxN7nU5P7GJNM6', -- password: 12345678
    NOW(),
    FALSE,
    NOW(),
    NOW()
);

-- ====================================================================
-- VISTAS ÚTILES PARA AUDITORÍA
-- ====================================================================

-- Vista: Estadísticas de 2FA
CREATE OR REPLACE VIEW `users_2fa_stats` AS
SELECT
    COUNT(*) as total_users,
    SUM(CASE WHEN two_factor_enabled = TRUE THEN 1 ELSE 0 END) as users_with_2fa,
    SUM(CASE WHEN two_factor_enabled = FALSE THEN 1 ELSE 0 END) as users_without_2fa,
    ROUND(
        SUM(CASE WHEN two_factor_enabled = TRUE THEN 1 ELSE 0 END) / COUNT(*) * 100, 2
    ) as percentage_with_2fa
FROM users;

-- ====================================================================
-- PROCEDIMIENTOS ALMACENADOS ÚTILES
-- ====================================================================

-- Procedimiento: Limpiar usuarios inactivos
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `cleanup_inactive_users`()
BEGIN
    -- Comentario: Este procedimiento elimina usuarios registrados hace más de 365 días sin confirmar 2FA
    DELETE FROM users
    WHERE email_verified_at IS NULL
    AND two_factor_confirmed_at IS NULL
    AND DATEDIFF(NOW(), created_at) > 365;
END //
DELIMITER ;

-- Procedimiento: Deshabilitar 2FA para usuarios inactivos
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `disable_2fa_for_inactive_users`(IN days_inactive INT)
BEGIN
    -- Comentario: Este procedimiento deshabilita 2FA para usuarios inactivos
    UPDATE users
    SET two_factor_enabled = FALSE,
        two_factor_secret = NULL,
        two_factor_confirmed_at = NULL
    WHERE DATEDIFF(NOW(), updated_at) > days_inactive
    AND two_factor_enabled = TRUE;
END //
DELIMITER ;

-- ====================================================================
-- DATOS FINALES
-- ====================================================================

-- Mostrar información de la base de datos creada
SELECT '========== BASE DE DATOS CREADA ==========' as mensaje;
SELECT 'Tabla users' as tabla, COUNT(*) as registros FROM users;
SELECT '========== ESTADÍSTICAS 2FA ==========' as mensaje;
SELECT * FROM users_2fa_stats;

-- ====================================================================
-- NOTAS IMPORTANTES
-- ====================================================================
/*
1. COLUMNAS 2FA:
   - two_factor_secret: Almacena el secret TOTP (codificado en Base32)
   - two_factor_enabled: Booleano que indica si 2FA está activo
   - two_factor_confirmed_at: Marca de tiempo de cuando se confirmó 2FA

2. SEGURIDAD:
   - Los secrets se almacenan en la BD pero deberían estar encriptados en producción
   - Las contraseñas se hashean con bcrypt (algoritmo 'password')
   - Se usan índices para mejorar performance en queries frecuentes

3. MANTENIMIENTO:
   - Se recomienda ejecutar 'cleanup_inactive_users' periódicamente
   - Hacer backup regulares de la BD
   - Monitorear la tabla 'failed_jobs' para errores

4. AUDITORÍA:
   - Los logs se almacenan en storage/logs/audit.log
   - Se registran: registro, login, errores de 2FA, etc.
*/
