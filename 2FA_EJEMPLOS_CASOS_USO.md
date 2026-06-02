# 📖 Guía de Ejemplos y Casos de Uso - Sistema 2FA

## 🎯 Caso 1: Registro e Instalación de 2FA

### Escenario
Un usuario nuevo se registra en el sistema y configura 2FA por primera vez.

### Pasos

#### 1. Acceder al formulario de registro
```
URL: http://localhost/register
```

#### 2. Completar formulario
```
Nombre: Juan García
Email: juan@ejemplo.com
Contraseña: MiPassword123!@
Confirmar: MiPassword123!@
```

#### 3. Validación AJAX en tiempo real
- El sistema valida el email mientras escribes
- Si el email ya existe, muestra: "Este correo ya está registrado"
- Si está disponible, muestra: "Correo disponible"

#### 4. Código PHP detrás del registro
```php
// RegisterController@register()
$user = User::create([
    'name' => $request->name,
    'email' => $request->email,
    'password' => Hash::make($request->password),
]);

// Se genera automáticamente el secret 2FA
$user->generateTwoFactorSecret();

// Se registra en logs
$this->auditLog->logUserRegistration([
    'name' => $user->name,
    'email' => $user->email,
]);

// Redirige a pantalla de setup 2FA
return redirect()->route('two-factor.setup');
```

#### 5. Configurar 2FA
```
Pantalla: /two-factor/setup
Mostrado:
- Código QR grande
- Clave secreta para entrada manual
- Campo para verificación de código

Usuario acciones:
1. Abre Google Authenticator
2. Toca "+" para agregar nueva cuenta
3. Escanea el QR code
4. Ingresa el código de 6 dígitos mostrado
5. Click en "Verificar y Habilitar 2FA"
```

#### 6. Base de datos después del registro
```sql
SELECT * FROM users WHERE email = 'juan@ejemplo.com';

-- Resultados:
id: 1
name: Juan García
email: juan@ejemplo.com
password: $2y$12$... (hasheada con bcrypt)
two_factor_secret: 'JRXQ2DBORGUTSIDBN5XCESLD...' (Base32)
two_factor_enabled: 1 (TRUE)
two_factor_confirmed_at: 2026-06-02 15:30:45
created_at: 2026-06-02 15:29:00
```

#### 7. Log de auditoría registrado
```json
[2026-06-02 15:29:00] ════════════════════════════════════════════════════════════════════════════════
{
  "event_type": "user_registered",
  "timestamp": "2026-06-02T15:29:00.000000Z",
  "user_id": null,
  "ip_address": "127.0.0.1",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)...",
  "url": "http://localhost/register",
  "method": "POST",
  "additional_data": {
    "email": "juan@ejemplo.com",
    "name": "Juan García"
  }
}
```

---

## 🎯 Caso 2: Login con 2FA Correcto

### Escenario
El usuario inicia sesión con contraseña correcta y código 2FA válido.

### Pasos

#### 1. Acceder a login
```
URL: http://localhost/login
```

#### 2. Ingresar credenciales
```
Email: juan@ejemplo.com
Contraseña: MiPassword123!@
```

#### 3. Sistema valida credenciales
```php
// LoginController@login()
$user = User::where('email', $request->email)->first();

// Verificar contraseña
if (!Hash::check($request->password, $user->password)) {
    // Registrar fallo
    $this->auditLog->logLoginFailed(
        $request->email,
        'invalid_credentials'
    );
    
    // Retornar error
    return redirect()->back()->withErrors([...]);
}

// Usuario existe y contraseña es correcta
if ($user->hasTwoFactorEnabled()) {
    // Crear sesión temporal 2FA
    session()->put('2fa_user_id', $user->id);
    
    // Registrar intento parcial
    $this->auditLog->log(
        AuditLogService::EVENT_LOGIN_SUCCESS,
        ['status' => 'pending_2fa'],
        (string)$user->id
    );
    
    // Redirigir a verificación
    return redirect()->route('two-factor.verify');
}
```

#### 4. Pantalla de verificación 2FA
```
URL: /two-factor/verify
Mostrado:
- Mensaje: "Por favor ingresa tu código de autenticación"
- Campo para código de 6 dígitos
- Botones: Verificar | Volver a Login
```

#### 5. Usuario ingresa código
- Abre Google Authenticator
- Ve código actual: `427895` (actualiza cada 30 segundos)
- Ingresa código en formulario
- Click en "Verificar"

#### 6. Sistema valida código TOTP
```php
// TwoFactorAuthController@validateCode()
$userId = session()->get('2fa_user_id');
$user = User::find($userId);

// Validar código TOTP
if (!$user->validateTwoFactorCode($request->code)) {
    $this->auditLog->logTwoFactorFailed($user->id, 'invalid_code_during_login');
    return back()->withErrors(['code' => 'Código inválido']);
}

// Código válido
Auth::login($user, $request->remember);
session()->forget('2fa_user_id');

$this->auditLog->logLoginSuccess($user->email, $user->id);

return redirect()->intended(route('home'));
```

#### 7. Resultado - Usuario autenticado
```
✓ Cookie de sesión creada
✓ Usuario redirigido a /home
✓ Puede acceder a recursos protegidos
✓ Logs registran login exitoso
```

#### 8. Logs finales
```
[user_registered] → [login_success + pending_2fa] → [two_factor_verified] → [login_success]
```

---

## 🎯 Caso 3: Login con Código 2FA Incorrecto

### Escenario
El usuario intenta login pero ingresa un código 2FA inválido.

### Flujo

#### 1. Credenciales y código incorrecto
```
Email: juan@ejemplo.com
Contraseña: MiPassword123!@  ✓ Correcta
Código 2FA: 999999           ✗ Incorrecto
```

#### 2. Validación del código
```php
// TwoFactorAuthController@validateCode()
$totp = TOTP::create($user->two_factor_secret);

// Verificar si el código es válido
// Los códigos TOTP solo son válidos en ventanas de 30 segundos
if (!$totp->verify($request->code, time(), 1)) {
    // Código inválido
    $this->auditLog->logTwoFactorFailed(
        $user->id,
        'invalid_code_during_login'
    );
    
    return back()
        ->withErrors(['code' => 'El código ingresado es inválido'])
        ->withInput();
}
```

#### 3. Usuario regresa a pantalla de verificación
```
Error mostrado: "El código ingresado es inválido. Intenta nuevamente."
Campo vacío para próximo intento
Usuario puede intentar de nuevo con nuevo código
```

#### 4. Log registrado
```json
{
  "event_type": "two_factor_failed",
  "timestamp": "2026-06-02T15:32:15.000000Z",
  "user_id": "1",
  "ip_address": "127.0.0.1",
  "additional_data": {
    "reason": "invalid_code_during_login"
  }
}
```

#### 5. Próximo intento
- Espera nuevo código en Google Authenticator (cada 30 segundos)
- Ingresa nuevo código: `428917`
- Si es válido → Login exitoso
- Si es inválido → Repite el error

---

## 🎯 Caso 4: Validación AJAX de Email Duplicado

### Escenario
Usuario intenta registrarse con un email que ya existe.

### Código HTML del formulario
```html
<form action="/register" method="POST">
    <!-- ... otros campos ... -->
    
    <div class="form-group">
        <label for="email">Correo Electrónico:</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            class="form-control"
            placeholder="tu@email.com"
            required
        >
        <div id="email-feedback"></div>
        <div id="email-status"></div>
    </div>
</form>
```

### JavaScript AJAX
```javascript
// Al escribir en el campo de email
emailInput.addEventListener('input', function() {
    const email = this.value.trim();
    
    if (!email) return;
    
    // Validar formato
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!regex.test(email)) {
        this.classList.add('is-invalid');
        return;
    }
    
    // Hacer request AJAX
    fetch(`/api/check-email?email=${email}`)
        .then(r => r.json())
        .then(data => {
            if (data.exists) {
                // Email ya existe
                emailInput.classList.add('is-invalid');
                emailFeedback.innerHTML = '✗ Este correo ya está registrado';
            } else {
                // Email disponible
                emailInput.classList.add('is-valid');
                emailStatus.innerHTML = '✓ Correo disponible';
            }
        });
});
```

### Backend - Endpoint API
```php
// RegistrationApiController@checkEmailExists()
public function checkEmailExists(Request $request): JsonResponse {
    $email = $request->query('email');
    
    // Buscar si existe
    $exists = User::where('email', $email)->exists();
    
    return response()->json([
        'available' => !$exists,
        'exists' => $exists,
        'message' => $exists 
            ? 'Este correo ya está registrado.' 
            : 'Correo disponible.',
    ]);
}
```

### Resultado Visual
```
Usuario escribe: juan@ejemplo.com
Spinner aparece: ⏳ Verificando...
Resultado 1: ✗ Este correo ya está registrado (si existe)
Resultado 2: ✓ Correo disponible (si no existe)

Botón "Registrarse" está:
- Deshabilitado si existe (is-invalid)
- Habilitado si disponible (is-valid)
```

---

## 🎯 Caso 5: Desabilitar 2FA

### Escenario
Usuario desea deshabilitar la autenticación de dos factores.

### Código

#### 1. Usuario accede a settings
```
URL: /dashboard (o /settings, según configuración)
Opción: "Desabilitar 2FA"
```

#### 2. Formulario de confirmación
```html
<form action="{{ route('two-factor.disable') }}" method="POST">
    @csrf
    
    <label>Para deshabilitar 2FA, ingresa tu contraseña:</label>
    <input type="password" name="password" required>
    <button type="submit">Desabilitar 2FA</button>
</form>
```

#### 3. Backend procesa la solicitud
```php
// TwoFactorAuthController@disable()
public function disable(Request $request): RedirectResponse {
    $user = Auth::user();
    
    // Verificar contraseña
    if (!Hash::check($request->password, $user->password)) {
        return back()->withErrors(['password' => 'Contraseña incorrecta']);
    }
    
    // Desabilitar 2FA
    $user->disableTwoFactor();
    
    // Registrar en logs
    $this->auditLog->logTwoFactorDisabled($user->id);
    
    return back()->with('success', '2FA deshabilitado');
}
```

#### 4. Cambios en Base de Datos
```sql
-- Antes:
two_factor_secret: 'JRXQ2DBORGUTSIDBN5XCESLD...'
two_factor_enabled: 1
two_factor_confirmed_at: 2026-06-02 15:30:45

-- Después:
two_factor_secret: NULL
two_factor_enabled: 0
two_factor_confirmed_at: NULL
```

#### 5. Log registrado
```json
{
  "event_type": "two_factor_disabled",
  "timestamp": "2026-06-02T15:35:20.000000Z",
  "user_id": "1",
  "ip_address": "127.0.0.1",
  "additional_data": {}
}
```

#### 6. Próximo login
- Usuario inicia sesión solo con email y contraseña
- NO se pide código 2FA
- Acceso directo al dashboard

---

## 📊 Análisis de Logs de Auditoría

### Ver todos los logs
```bash
# Linux/Mac
tail -f storage/logs/audit.log

# Windows PowerShell
Get-Content storage/logs/audit.log -Wait

# O via PHP Artisan
php artisan tail
```

### Filtrar logs específicos
```bash
# Solo logins fallidos
grep "login_failed" storage/logs/audit.log

# Solo errores 2FA
grep "two_factor_failed" storage/logs/audit.log

# Actividad de un usuario específico
grep '"user_id": "1"' storage/logs/audit.log

# Últimas 20 líneas
tail -20 storage/logs/audit.log
```

### Formato JSON para análisis
```bash
# Extraer solo timestamps y eventos
grep '"event_type"' storage/logs/audit.log | jq '.event_type'

# Contar eventos por tipo
grep '"event_type"' storage/logs/audit.log | jq '.event_type' | sort | uniq -c
```

---

## 🔒 Tabla de Seguridad - Medidas Implementadas

| Medida | Implementado | Descripción |
|--------|-------------|------------|
| **Hashing bcrypt** | ✓ | Contraseñas con 12 rondas |
| **CSRF Token** | ✓ | Protección contra CSRF |
| **TOTP válido 30s** | ✓ | Código válido solo una ventana |
| **Rate limiting** | ✓ | Máximo intentos de login |
| **Session temporal** | ✓ | 2FA requiere sesión separada |
| **Input sanitization** | ✓ | Validación de todos los inputs |
| **SQL injection** | ✓ | Eloquent ORM previene inyección |
| **XSS protection** | ✓ | Blade escapa HTML automáticamente |
| **Logs de auditoría** | ✓ | Registro de todas las acciones |
| **Email verification** | ✓ | Validación AJAX de duplicados |

---

## 💡 Tips Útiles

### 1. Códigos de Prueba
Usa estos emails/contraseñas para testing:
```
Email: admin@test.com
Contraseña: Admin@123456

Email: user@test.com
Contraseña: User@123456
```

### 2. Resetear 2FA para un usuario (BD)
```sql
UPDATE users 
SET 
    two_factor_secret = NULL,
    two_factor_enabled = FALSE,
    two_factor_confirmed_at = NULL
WHERE email = 'usuario@ejemplo.com';
```

### 3. Ver códigos TOTP en tiempo real (PHP)
```php
// En tinker: php artisan tinker
$user = User::first();
$totp = OTPHP\TOTP::create($user->two_factor_secret);
echo $totp->now(); // Muestra código actual
```

### 4. Limpiar logs
```bash
# Vaciar archivo de logs
> storage/logs/audit.log

# O desde PHP
AuditLogService::clearLog();
```

---

## ✅ Checklist de Testing

- [ ] Registrar usuario exitosamente
- [ ] Validación AJAX email duplicado funciona
- [ ] Setup de 2FA muestra QR correcto
- [ ] Codigo TOTP verificación exitosa
- [ ] Login sin 2FA fallido (no permitido)
- [ ] Login con código incorrecto muestra error
- [ ] Desabilitar 2FA funciona
- [ ] Logs se crean correctamente
- [ ] CSRF tokens presentes
- [ ] Validación de inputs funciona

---

**¡Todos los casos de uso están documentados y listos para testing!** ✅

