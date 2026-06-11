/*
  Script SQL completo para AutenticacionLab.
  Ejecutar este archivo con un usuario administrador de MySQL solo para crear
  la base de datos, el usuario dedicado y las tablas.
*/

CREATE DATABASE IF NOT EXISTS company_info
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

/*
  Usuario dedicado para la aplicacion. No se usa root desde PHP.
  Cambia la contrasena en produccion.
*/
CREATE USER IF NOT EXISTS 'ComodinUser7'@'localhost' IDENTIFIED BY 'mySecreto27';
CREATE USER IF NOT EXISTS 'ComodinUser7'@'127.0.0.1' IDENTIFIED BY 'mySecreto27';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP
ON company_info.* TO 'ComodinUser7'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP
ON company_info.* TO 'ComodinUser7'@'127.0.0.1';
FLUSH PRIVILEGES;

USE company_info;

/*
  Tabla principal solicitada: usuarios.
  HashMagic almacena el hash generado por password_hash().
  secret_2fa almacena el secreto Base32 generado por Sonata GoogleAuthenticator.
*/
CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  correo VARCHAR(255) NOT NULL,
  HashMagic VARCHAR(255) NOT NULL,
  sexo ENUM('Masculino', 'Femenino', 'Otro') NOT NULL,
  secret_2fa VARCHAR(255) NOT NULL,
  two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
  two_factor_confirmed_at TIMESTAMP NULL DEFAULT NULL,
  remember_token VARCHAR(100) NULL DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY usuarios_correo_unique (correo),
  KEY usuarios_two_factor_enabled_index (two_factor_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*
  Tokens de recuperacion compatibles con Laravel UI.
*/
CREATE TABLE IF NOT EXISTS password_reset_tokens (
  email VARCHAR(255) NOT NULL,
  token VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*
  Sesiones de la aplicacion. Laravel puede guardar aqui el estado temporal
  del login y el usuario pendiente de 2FA.
*/
CREATE TABLE IF NOT EXISTS sessions (
  id VARCHAR(255) NOT NULL,
  user_id BIGINT UNSIGNED NULL DEFAULT NULL,
  ip_address VARCHAR(45) NULL DEFAULT NULL,
  user_agent TEXT NULL DEFAULT NULL,
  payload LONGTEXT NOT NULL,
  last_activity INT NOT NULL,
  PRIMARY KEY (id),
  KEY sessions_user_id_index (user_id),
  KEY sessions_last_activity_index (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*
  Tablas auxiliares usadas por cache y colas si se activan desde Laravel.
*/
CREATE TABLE IF NOT EXISTS cache (
  `key` VARCHAR(255) NOT NULL,
  value MEDIUMTEXT NOT NULL,
  expiration INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cache_locks (
  `key` VARCHAR(255) NOT NULL,
  owner VARCHAR(255) NOT NULL,
  expiration INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jobs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  queue VARCHAR(255) NOT NULL,
  payload LONGTEXT NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL,
  reserved_at INT UNSIGNED NULL DEFAULT NULL,
  available_at INT UNSIGNED NOT NULL,
  created_at INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY jobs_queue_index (queue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS job_batches (
  id VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  total_jobs INT NOT NULL,
  pending_jobs INT NOT NULL,
  failed_jobs INT NOT NULL,
  failed_job_ids LONGTEXT NOT NULL,
  options MEDIUMTEXT NULL DEFAULT NULL,
  cancelled_at INT NULL DEFAULT NULL,
  created_at INT NOT NULL,
  finished_at INT NULL DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'AutenticacionLab SQL instalado correctamente' AS mensaje;
