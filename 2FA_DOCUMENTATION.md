# 🔐 Sistema de Autenticación con Doble Factor (2FA)

## 📋 Descripción General

Sistema completo de autenticación con **doble factor (2FA)** implementado en **Laravel 13** usando **Google Authenticator** (TOTP). 

El sistema permite a los usuarios:
- Registrarse con credenciales seguras
- Configurar autenticación de dos factores automáticamente
- Escanear código QR con Google Authenticator
- Iniciar sesión en dos pasos: credenciales + código TOTP
- Validar emails duplicados mediante AJAX
- Ver logs de auditoría de todas las acciones

---

## 🛠️ Tecnologías Utilizadas

| Tecnología | Versión | Propósito |
|------------|---------|----------|
| **Laravel** | 13.x | Framework web |
| **PHP** | 8.3+ | Lenguaje de programación |
| **MySQL** | 8.0+ | Base de datos |
| **Bootstrap** | 5.x | Diseño responsivo |
| **OTPHP** | 11.2 | Generación de códigos TOTP |
| **PragmaRX Google2FA** | 8.0 | Verificación de 2FA |
| **Endroid QR Code** | 5.0 | Generación de códigos QR |
| **jQuery** | 3.x | Validaciones AJAX |

---

## 📁 Estructura de Archivos

```
proyecto/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── LoginController.php      ← Login con verificación 2FA
│   │   │   │   └── RegisterController.php   ← Registro con generación de secret
│   │   │   ├── TwoFactorAuthController.php  ← Controlador principal 2FA
│   │   │   └── Api/
│   │   │       └── RegistrationApiController.php  ← Validación AJAX
│   │   └── Middleware/
│   │       └── VerifyTwoFactor.php  ← Middleware para sesiones 2FA temporal
│   ├── Models/
│   │   └── User.php  ← Modelo con métodos 2FA
│   └── Services/
│       └── AuditLogService.php  ← Sistema de logs
│
├── database/
│   ├── migrations/
│   │   └── 2026_06_02_135026_add_two_factor_fields_to_users.php
│   └── sql/
│       └── install.sql  ← Script SQL completo
│
├── resources/
│   ├── views/
│   │   └── auth/
│   │       └── two-factor/
│   │           ├── setup.blade.php         ← Pantalla de setup con QR
│   │           ├── verify.blade.php        ← Verificación durante login
│   │           └── already-enabled.blade.php
│   └── js/
│       └── auth/
│           └── email-validation.js  ← Validación AJAX
│
├── routes/
│   └── web.php  ← Rutas actualizadas con endpoints 2FA
│
└── storage/
    └── logs/
        └── audit.log  ← Logs de auditoría

```

---

## 🚀 Instalación y Configuración

### Paso 1: Instalar Dependencias

```bash
cd c:/wamp64/www/LoginLaravel/Laboratorio1Laravel

# Instalar dependencias PHP
composer install

# Instalar dependencias Node
npm install
```

### Paso 2: Configurar Variables de Entorno

El archivo `.env` ya está configurado, pero verifica estos valores:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laboratoriolaravel
DB_USERNAME=tu_usuario  # NO usar root
DB_PASSWORD=tu_contraseña

SESSION_DRIVER=database
SESSION_LIFETIME=120
```

### Paso 3: Ejecutar Migraciones

```bash
# Ejecutar todas las migraciones (incluyendo 2FA)
php artisan migrate --force

# O sin force si te pide confirmación
php artisan migrate
```

### Paso 4: Compilar Assets

```bash
# Compilar CSS y JavaScript
npm run build

# O para desarrollo con watch
npm run dev
```

### Paso 5: Iniciar Servidor

```bash
# Opción 1: Servidor artisan (desarrollo)
php artisan serve

# Opción 2: Si usas WAMP/Apache
# Acceder en navegador: http://localhost/LoginLaravel/Laboratorio1Laravel/public
```

---

## 👤 Prueba del Sistema

### 1️⃣ Registro de Usuario

1. Ir a `http://localhost/register`
2. Rellenar formulario:
   - **Nombre:** Tu nombre
   - **Correo:** tu_email@ejemplo.com
   - **Contraseña:** Min. 8 caracteres
3. Validación AJAX verifica email disponible en tiempo real
4. Click en "Registrarse"

### 2️⃣ Configuración de 2FA

Después del registro, se muestra pantalla de setup:

1. **Escanear QR:** Abre Google Authenticator y escanea el código
2. **O ingresa manualmente:** Copia la clave secreta en tu app
3. **Verifica código:** Ingresa un código TOTP de 6 dígitos
4. Click en "Verificar y Habilitar 2FA"

### 3️⃣ Login con 2FA

1. Ir a `http://localhost/login`
2. Ingresa email y contraseña
3. Se abre pantalla de verificación 2FA
4. Abre Google Authenticator y copia el código
5. Ingresa los 6 dígitos
6. ¡Login exitoso!

### 📱 Descarga Google Authenticator

- **iOS:** [Apple App Store](https://apps.apple.com/es/app/google-authenticator/id388497605)
- **Android:** [Google Play](https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2)
- **Alternativas:** Microsoft Authenticator, Authy, FreeOTP

---

## 📝 Documentación de Código

### User.php - Modelo

```php
// Generar secret 2FA
$user->generateTwoFactorSecret();

// Obtener URL del QR code (para mostrar en vista)
$qrUrl = $user->getTwoFactorQrCodeUrl();

// Validar código TOTP (retorna bool)
if ($user->validateTwoFactorCode('123456')) {
    // Código válido
}

// Habilitar 2FA después de verificar
$user->enableTwoFactor('123456');

// Deshabilitar 2FA
$user->disableTwoFactor();

// Verificar si está habilitado
if ($user->hasTwoFactorEnabled()) {
    // Redireccionar a verificación 2FA
}
```

### TwoFactorAuthController.php

```php
// GET /two-factor/setup - Mostrar QR
public function setup(): View

// POST /two-factor/confirm - Confirmar y habilitar
public function confirm(Request $request): RedirectResponse

// GET /two-factor/verify - Pantalla verificación login
public function verify(): View|RedirectResponse

// POST /two-factor/verify - Validar código durante login
public function validateCode(Request $request): RedirectResponse

// POST /two-factor/disable - Deshabilitar 2FA
public function disable(Request $request): RedirectResponse
```

### AuditLogService.php

```php
// Registrar evento personalizado
$auditLog->log(
    AuditLogService::EVENT_LOGIN_SUCCESS,
    ['email' => 'user@ejemplo.com'],
    $userId,
    $ipAddress
);

// Métodos específicos disponibles:
$auditLog->logUserRegistration($userData);
$auditLog->logLoginSuccess($email, $userId);
$auditLog->logLoginFailed($email, $reason);
$auditLog->logTwoFactorSetup($userId);
$auditLog->logTwoFactorVerified($userId);
$auditLog->logTwoFactorFailed($userId, $reason);
$auditLog->logTwoFactorDisabled($userId);
$auditLog->logLogout($userId);
```

---

## 🔐 Características de Seguridad

### ✅ Implementado

- [x] **Hashing de Contraseñas:** Bcrypt con 12 rondas
- [x] **Validación CSRF:** Token de sesión en todos los formularios
- [x] **Validación de Entrada:** Sanitización y validación de datos
- [x] **Sesiones Temporales:** 2FA requiere sesión temporal segura
- [x] **Rate Limiting:** Previene ataques de fuerza bruta en login
- [x] **Secret Storage:** Secrets guardados encriptados en BD
- [x] **TOTP Verificación:** Código válido solo en ventana de 30 segundos
- [x] **Logs de Auditoría:** Registro de todas las acciones críticas
- [x] **HTTPS Ready:** Compatible con SSL/TLS

---

## 📊 Logs de Auditoría

Todos los eventos se registran en `storage/logs/audit.log`

### Estructura de Log

```json
{
  "event_type": "login_success",
  "timestamp": "2026-06-02T15:30:45.123456Z",
  "user_id": 1,
  "ip_address": "192.168.1.100",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)...",
  "url": "http://localhost/login",
  "method": "POST",
  "additional_data": {
    "email": "usuario@ejemplo.com"
  }
}
```

### Tipos de Eventos Registrados

| Evento | Descripción |
|--------|-------------|
| `user_registered` | Nuevo usuario registrado |
| `login_success` | Login exitoso |
| `login_failed` | Intento fallido de login |
| `two_factor_setup` | Configuración de 2FA iniciada |
| `two_factor_verified` | 2FA verificado correctamente |
| `two_factor_failed` | Código 2FA inválido |
| `two_factor_disabled` | 2FA deshabilitado |
| `logout` | Usuario cerró sesión |

---

## 🔧 Rutas API

### Registración

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/register` | Formulario de registro |
| POST | `/register` | Procesar registro |

### Autenticación

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/login` | Formulario de login |
| POST | `/login` | Procesar login |
| POST | `/logout` | Cerrar sesión |

### 2FA

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/two-factor/setup` | Mostrar QR y secret |
| POST | `/two-factor/confirm` | Confirmar y habilitar 2FA |
| GET | `/two-factor/verify` | Pantalla verificación 2FA |
| POST | `/two-factor/verify` | Validar código TOTP |
| POST | `/two-factor/disable` | Deshabilitar 2FA |

### API AJAX

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/check-email?email=...` | Verificar si email existe |
| POST | `/api/validate-email` | Validar email en tiempo real |

---

## 📋 Base de Datos

### Tabla `users` - Campos Nuevos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `two_factor_secret` | VARCHAR(255) | Secret TOTP en Base32 |
| `two_factor_enabled` | BOOLEAN | ¿Está 2FA habilitado? |
| `two_factor_confirmed_at` | TIMESTAMP | Fecha confirmación |

### Consultas Útiles

```sql
-- Ver usuarios con 2FA habilitado
SELECT id, email, two_factor_enabled, two_factor_confirmed_at 
FROM users 
WHERE two_factor_enabled = TRUE;

-- Estadísticas de 2FA
SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN two_factor_enabled THEN 1 ELSE 0 END) as con_2fa,
    SUM(CASE WHEN two_factor_enabled = FALSE THEN 1 ELSE 0 END) as sin_2fa
FROM users;

-- Usuarios registrados recientemente
SELECT id, email, created_at, two_factor_enabled 
FROM users 
ORDER BY created_at DESC 
LIMIT 10;
```

---

## 🐛 Troubleshooting

### Error: "No password supplied"

**Problema:** Mensaje de error en el upload de archivo

**Solución:**
```bash
composer update

# Limpiar cache
php artisan config:clear
php artisan cache:clear
```

### Error: "Unknown column 'two_factor_secret'"

**Problema:** Columnas 2FA no existen en la BD

**Solución:**
```bash
# Ejecutar migraciones
php artisan migrate

# O reimportar SQL:
# mysql -u usuario laboratoriolaravel < database/sql/install.sql
```

### Validación AJAX no funciona

**Problema:** El endpoint `/api/check-email` retorna 404

**Solución:**
1. Verifica que las rutas estén registradas en `routes/web.php`
2. Limpia cache de rutas: `php artisan route:clear`
3. Verifica en Console del navegador (F12) si hay errores

### Código 2FA siempre inválido

**Problema:** Los códigos TOTP se rechazan

**Soluciones:**
1. **Sincronizar hora del servidor:** El reloj debe estar sincronizado
   ```bash
   # En Linux/Mac
   ntpdate -s time.nist.gov
   ```

2. **Aumentar discrepancia de tiempo:** En `User.php`, cambiar:
   ```php
   return $totp->verify($code, time(), 2); // De 1 a 2 ventanas
   ```

3. **Verificar secret generado:** El secret debe ser válido Base32

---

## 📚 Referencias Útiles

### Documentación Oficial

- [Laravel Docs](https://laravel.com/docs/13.x)
- [OTPHP Documentation](https://github.com/Spomky-Labs/otphp)
- [Google Authenticator](https://support.google.com/accounts/answer/1066447)
- [TOTP Specification (RFC 6238)](https://tools.ietf.org/html/rfc6238)

### Recursos

- [Bootstrap 5 Docs](https://getbootstrap.com/docs/5.0/)
- [jQuery Documentation](https://api.jquery.com/)
- [MySQL Documentation](https://dev.mysql.com/doc/)

---

## 📝 Licencia

Este proyecto es parte del laboratorio de Laravel y está disponible bajo licencia MIT.

---

## 👨‍💻 Información del Desarrollador

- **Versión:** 1.0.0
- **Última Actualización:** Junio 2, 2026
- **Compatibilidad:** PHP 8.3+, Laravel 13+, MySQL 8.0+

---

## 📞 Soporte

Para problemas o preguntas:

1. Revisar los logs: `storage/logs/audit.log`
2. Ejecutar debug mode: `APP_DEBUG=true` en `.env`
3. Usar `php artisan tinker` para testing interactivo

---

**¡Sistema 2FA completamente funcional y listo para producción!** 🎉

