# Autenticación y Seguridad

## Flujo de Registro

```
POST /register
  ├── Validar: name, email (unique), password (min8+mayúsculas+números), terms
  ├── Crear User (role=buyer, is_active=1, ULID auto)
  ├── Crear Wallet NT (balance=0)
  ├── event(Registered) → dispara envío email verificación
  ├── $user->notify(WelcomeNotification) → cola → email de bienvenida
  ├── AuditLog::record('registered')
  ├── Auth::login($user)
  └── redirect → /email/verify
```

## Flujo de Login

```
POST /login
  ├── Validar: email, password
  ├── Rate limiting: 5 intentos / 15 min por IP+email
  │   └── Si excede → error 429 con segundos restantes
  ├── Auth::attempt() 
  │   ├── FALLA → RateLimiter::hit() + AuditLog('login_failed')
  │   └── OK    → continuar
  ├── Verificar $user->is_active (si false → logout + error)
  ├── $user->update(['last_login_at' => now()])
  ├── AuditLog::record('login_success')
  ├── $request->session()->regenerate()
  └── redirect → intended ó home
```

## Flujo OAuth (Google / Steam)

```
GET /auth/google → Socialite::driver('google')->redirect()
GET /auth/google/callback
  ├── Socialite::driver('google')->user()
  ├── User::firstOrCreate(['email' => $socialUser->getEmail()])
  │   ├── Si NUEVO → crear Wallet NT + AuditLog('registered_oauth')
  │   └── Si EXISTE → actualizar provider/avatar + AuditLog('login_oauth')
  ├── $user->update(['last_login_at' => now()])
  ├── Auth::login($user, remember=true)
  └── redirect → home
```

> **Steam:** usa OpenID. El email no siempre está disponible, se usa `{steam_id}@steam.local` como fallback.

## Verificación de Email

| Ruta | Acción |
|---|---|
| `GET /email/verify` | Página de aviso (notice) |
| `GET /email/verify/{id}/{hash}` | Verifica el link firmado |
| `POST /email/verification-notification` | Reenvía el email (throttle 6/min) |

> **En desarrollo:** `MAIL_MAILER=log` — el link aparece en `storage/logs/laravel.log`

## Recuperación de Contraseña

```
POST /forgot-password {email}
  └── Password::sendResetLink() → email con token (expira en 60 min)

POST /reset-password {token, email, password, password_confirmation}
  ├── Password::reset()
  ├── AuditLog::record('password_reset')
  └── redirect → /login
```

---

## Roles del Sistema

| Rol | Descripción | Acceso |
|---|---|---|
| `buyer` | Comprador registrado | Tienda, carrito, órdenes, licencias, wallet |
| `seller` | Vendedor (solo Línea Once / AGX) | + Panel vendedor (/seller/*) |
| `admin` | Administrador | + Panel admin (/admin/*) + todo |

### Asignación de Roles

- **buyer** → asignado automáticamente al registrarse
- **seller** → asignado manualmente por un admin vía panel
- **admin** → solo por migración/seeder o por otro admin

### Middleware de Roles

```php
// En rutas:
Route::middleware(['auth', 'role:seller,admin'])->group(...)

// EnsureUserHasRole.php:
// - Acepta múltiples roles: role:seller,admin
// - Si API → responde 403 JSON
// - Si web → redirect con mensaje de error
```

---

## Seguridad

### Rate Limiting
- **Login:** 5 intentos por IP+email → bloqueo 15 minutos
- **Reenviar verificación:** 6 por minuto (throttle middleware)
- **API general:** configurado en RouteServiceProvider

### Claves Digitales Encriptadas
```php
// Las key_value se guardan con Laravel encrypt()
$key->key_value = encrypt($rawKey);

// Al entregar al comprador:
$rawKey = decrypt($key->key_value);
```

### Headers de Seguridad (Nginx)
```nginx
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header X-XSS-Protection "1; mode=block";
add_header Referrer-Policy "strict-origin-when-cross-origin";
```

### Audit Log — Eventos registrados

| Evento | Cuándo |
|---|---|
| `registered` | Registro exitoso |
| `registered_oauth` | Registro vía OAuth |
| `login_success` | Login exitoso |
| `login_failed` | Credenciales incorrectas |
| `login_oauth` | Login vía OAuth |
| `logout` | Cierre de sesión |
| `email_verified` | Email verificado |
| `password_reset` | Contraseña restablecida |
| `password_changed` | Contraseña cambiada en perfil |

### 2FA (Two-Factor Authentication)

La tabla `two_factor_auth` almacena el secreto TOTP encriptado y los códigos de recuperación. El flujo completo de activación se implementará en la Etapa de Seguridad avanzada.

---

## Variables de Entorno Relevantes

```env
# Seguridad
BCRYPT_ROUNDS=12
SESSION_SECURE_COOKIE=false  # true en producción
SECURITY_MAX_LOGIN_ATTEMPTS=5
SECURITY_LOCKOUT_MINUTES=15
SECURITY_2FA_ISSUER="Nexo Digital Store"
SECURITY_AUDIT_ENABLED=true

# OAuth Google
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=

# OAuth Steam
STEAM_API_KEY=

# Mail (verificación + bienvenida)
MAIL_MAILER=log        # desarrollo
MAIL_MAILER=smtp       # producción
MAIL_FROM_ADDRESS="noreply@nexodigitalstore.com"
```

---

## Configuración OAuth en Google Console

1. Ir a [console.cloud.google.com](https://console.cloud.google.com)
2. Crear proyecto → Habilitar Google+ API
3. Credenciales → OAuth 2.0 Client ID
4. Authorized redirect URIs: `http://localhost:8000/auth/google/callback`
5. Copiar `client_id` y `client_secret` al `.env`
