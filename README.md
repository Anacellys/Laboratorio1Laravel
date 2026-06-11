# 🔐 AutenticacionLab — Sistema de Autenticación con Doble Factor (2FA)

> Universidad Tecnológica de Panamá  

---

## 📌 Descripción General

**AutenticacionLab** es un sistema web de autenticación segura que implementa el **doble factor de autenticación (2FA)** usando códigos TOTP (Time-Based One-Time Password) compatibles con aplicaciones como **Google Authenticator** o **Authy**.

El proyecto fue desarrollado siguiendo los principios de **Programación Orientada a Objetos (POO)**, arquitectura **MVC** con Laravel 13, separación entre lógica y presentación, y buenas prácticas de seguridad web.

---

## 🎯 Funcionalidades Implementadas

| # | Funcionalidad | Estado |
|---|---------------|--------|
| 1 | Registro de usuarios con validación AJAX | ✅ |
| 2 | Verificación de email duplicado en tiempo real | ✅ |
| 3 | Encriptación de contraseñas con bcrypt (12 rondas) | ✅ |
| 4 | Generación automática de secret TOTP al registrarse | ✅ |
| 5 | Almacenamiento del secret encriptado en base de datos | ✅ |
| 6 | Generación de código QR compatible con Google Authenticator | ✅ |
| 7 | Pantalla de configuración de 2FA post-registro | ✅ |
| 8 | Login con correo y contraseña | ✅ |
| 9 | Segunda pantalla de verificación con código TOTP | ✅ |
| 10 | Validación del código de Google Authenticator | ✅ |
| 11 | Acceso bloqueado si el código es incorrecto | ✅ |
| 12 | Manejo de sesiones con driver de base de datos | ✅ |
| 13 | Logs de auditoría persistentes en `audit.log` | ✅ |
| 14 | Interfaz responsive con Bootstrap 5 | ✅ |
| 15 | Script SQL completo con tablas, vistas y datos de prueba | ✅ |

---


## 🛠️ Tecnologías Utilizadas

| Tecnología | Versión | Uso |
|------------|---------|-----|
| PHP | 8.2+ | Lenguaje backend |
| Laravel | 13 | Framework MVC |
| MySQL | 8.0+ | Base de datos relacional |
| Bootstrap | 5 | Estilos y diseño responsive |
| OTPHP | ^11.2 | Generación de códigos TOTP |
| PragmaRX Google2FA | ^8.0 | Validación de códigos |
| Endroid QR Code | ^5.0 | Generación de imágenes QR |
| Vite | — | Compilación de assets JS/CSS |
| WAMP64 | — | Entorno de desarrollo local |

---

## ⚙️ Instalación y Configuración

### Requisitos previos
- PHP 8.2 o superior
- Composer instalado
- MySQL 8.0+ corriendo (WAMP/XAMPP)
- Node.js y npm instalados

### Paso 1 — Clonar el proyecto

```bash
cd C:/wamp64/www/LoginLaravel
git clone <url-repositorio> Laboratorio1Laravel
cd Laboratorio1Laravel
```

### Paso 2 — Instalar dependencias

```bash
composer install
npm install
```

### Paso 3 — Configurar el entorno

Copia el archivo de entorno y configura tus variables:

```bash
cp .env.example .env
php artisan key:generate
```

Edita el `.env` con tus datos de base de datos:

```dotenv
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=company_info
DB_USERNAME=ComodinUser7
DB_PASSWORD=mySecreto27
```

### Paso 4 — Crear la base de datos

Ejecuta el script SQL incluido en el proyecto:

```bash
mysql -u ComodinUser7 -p company_info < database/sql/install.sql
```

O usa las migraciones de Laravel:

```bash
php artisan migrate --force
```

### Paso 5 — Compilar assets y levantar el servidor

```bash
npm run build
php artisan serve
```

Accede desde el navegador en: **http://localhost:8000**

---

## 🔄 Flujo del Sistema

```
1. Usuario se registra en /register
        ↓
2. Sistema genera secret TOTP automáticamente
        ↓
3. Redirige a /two-factor/setup (muestra QR)
        ↓
4. Usuario escanea QR con Google Authenticator
        ↓
5. Usuario confirma con primer código TOTP
        ↓
6. Sistema habilita 2FA y guarda en base de datos
        ↓
7. Usuario hace logout y va a /login
        ↓
8. Ingresa correo y contraseña correctos
        ↓
9. Sistema detecta 2FA activo → redirige a /two-factor/verify
        ↓
10. Usuario ingresa código de 6 dígitos de la app
        ↓
11a. Código correcto → acceso completo al sistema ✅
11b. Código incorrecto → error visible, acceso denegado ❌
```

---

## 🗄️ Base de Datos

### Tabla `users` (extendida)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | bigint | Clave primaria |
| `name` | varchar(255) | Nombre del usuario |
| `email` | varchar(255) | Correo único |
| `password` | varchar(255) | Hash bcrypt |
| `two_factor_secret` | text | Secret TOTP encriptado |
| `two_factor_enabled` | boolean | Estado del 2FA |
| `two_factor_confirmed_at` | timestamp | Fecha de activación |
| `created_at` | timestamp | Fecha de registro |
| `updated_at` | timestamp | Última actualización |

### Conexión configurada

```
Host:     localhost
Puerto:   3306
Base:     company_info
Usuario:  ComodinUser7
Charset:  utf8mb4
```

---

## 🧩 Arquitectura del Código (POO + MVC)

### Modelo — `User.php`
Contiene los métodos helper para toda la lógica de 2FA:

```php
generateTwoFactorSecret()    // Genera secret TOTP único
getTwoFactorQrCodeUrl()      // Retorna URL para imagen QR
validateTwoFactorCode($code) // Valida código contra secret
enableTwoFactor()            // Activa 2FA en la cuenta
disableTwoFactor()           // Desactiva 2FA
hasTwoFactorEnabled()        // Retorna true/false del estado
```

### Controlador — `TwoFactorAuthController.php`
Maneja todas las rutas relacionadas con el flujo de 2FA:

```php
setup()         // Muestra QR y secret para configurar
confirm()       // Confirma y habilita 2FA con primer código
verify()        // Muestra pantalla de verificación durante login
validateCode()  // Valida código TOTP en login
disable()       // Desactiva 2FA de la cuenta
```

### Servicio — `AuditLogService.php`
Registra todos los eventos importantes de seguridad en `storage/logs/audit.log`:
- Intentos de login (exitosos y fallidos)
- Activación/desactivación de 2FA
- Validaciones de código incorrectas

### Middleware — `VerifyTwoFactor.php`
Protege las rutas durante el proceso de verificación, asegurando que la sesión temporal de 2FA sea válida antes de permitir el acceso.

---

## 🔐 Seguridad Implementada

- **Bcrypt** con 12 rondas para contraseñas
- **CSRF tokens** en todos los formularios Blade
- **Sesiones temporales** durante el proceso de 2FA
- **Validación de entrada** en controladores y formularios
- **Secret TOTP encriptado** en base de datos
- **Logs de auditoría** con fecha, hora e IP de cada evento
- **Usuario no-root** para conexión a MySQL

---

## 📋 Comandos Útiles

```bash
# Limpiar caché de configuración (si hay errores raros)
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Correr migraciones pendientes
php artisan migrate --force

# Ver logs de auditoría en tiempo real (Linux/Mac)
tail -f storage/logs/audit.log

# Ver logs en Windows (PowerShell)
Get-Content storage/logs/audit.log -Wait
```

---

## 🐛 Solución de Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| `Unknown column 'two_factor_secret'` | Migración no ejecutada | `php artisan migrate --force` |
| Validación AJAX no funciona | Caché de rutas desactualizada | `php artisan route:clear` |
| Código 2FA siempre inválido | Reloj del sistema desincronizado | Verificar hora del sistema |
| Error de sintaxis en `.env` | Caracteres especiales sin comillas | Rodear valores con `"..."` |
| `array_merge(): Argument #2 must be array` | Código ajeno dentro de `config/database.php` | Restaurar `database.php` original de Laravel |

---

## 📚 Dependencias del Proyecto

```json
{
  "require": {
    "spomky-labs/otphp": "^11.2",
    "pragmarx/google2fa": "^8.0",
    "endroid/qr-code": "^5.0"
  }
}
```

---

## 👩‍💻 Información del Proyecto

| Campo | Detalle |
|-------|---------|
| **Asignatura** | Desarrollo de Sotfware VII|
| **Universidad** | Universidad Tecnológica de Panamá |
| **Tipo** | Laboratorio de Autenticación  |
| **Fecha** | Junio 2026 |

---

