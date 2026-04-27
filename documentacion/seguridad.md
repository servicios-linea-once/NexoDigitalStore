# Seguridad — Guía Completa

## Capas de Seguridad

```
┌─────────────────────────────────────────────────────┐
│ 1. Nginx         — Headers HTTP de seguridad         │
│ 2. Laravel       — CSRF, XSS, SQL injection          │
│ 3. Auth          — Rate limiting, 2FA TOTP           │
│ 4. Middleware    — Roles, cuenta activa              │
│ 5. Modelo        — Encriptación de claves digitales  │
│ 6. Audit Log     — Trazabilidad de eventos críticos  │
└─────────────────────────────────────────────────────┘
```

---

## Headers HTTP de Seguridad (Nginx)

```nginx
# docker/nginx/default.conf
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;

# En producción agregar:
add_header Content-Security-Policy "default-src 'self'; ...";
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";
```

---

## CSRF Protection

Laravel protege automáticamente todas las rutas POST/PUT/DELETE del grupo `web`. Los webhooks de PayPal/MercadoPago/Telegram excluyen esta protección:

```php
// routes/web.php
Route::prefix('webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->group(function () { ... });
```

Los webhooks verifican la firma del gateway externamente (PAYPAL-TRANSMISSION-SIG, X-MP-Signature).

---

## Rate Limiting

| Endpoint | Límite |
|---|---|
| `POST /login` | 5 intentos / 15 min por IP+email |
| `POST /email/verification-notification` | 6 / minuto |
| `POST /forgot-password` | 6 / minuto |
| `API /api/v1/*` | 60 / minuto por token |
| `API /api/v1/auth/*` | 10 / minuto por IP |

---

## Encriptación de Claves Digitales

```php
// Al importar claves (SellerKeyController@store)
foreach ($keys as $rawKey) {
    DigitalKey::create([
        'product_id' => $product->id,
        'seller_id'  => $seller->id,
        'key_value'  => encrypt($rawKey),  // AES-256-CBC via Laravel
        'status'     => 'available',
    ]);
}

// Al entregar al comprador (solo cuando orden está COMPLETED)
$key = DigitalKey::find($id);
$revealedKey = decrypt($key->key_value);

// NUNCA se devuelve en listados — solo en la vista de orden completada
```

**Clave de encriptación:** `APP_KEY` en `.env` — rotarla invalida todas las claves guardadas.

---

## 2FA — Two-Factor Authentication (TOTP)

### Tabla `two_factor_auth`

```sql
id                   BIGINT UNSIGNED  PK
user_id              BIGINT UNSIGNED  FK UNIQUE
secret               TEXT             NOT NULL (encriptado en DB)
recovery_codes       TEXT             NULLABLE (encriptado — 8 códigos)
is_enabled           TINYINT(1)       DEFAULT 0
confirmed_at         TIMESTAMP        NULLABLE
created_at, updated_at
```

### Flujo de Activación

```
1. GET /2fa/setup → mostrar QR + código secreto
2. Usuario escanea con Google Authenticator / Authy
3. POST /2fa/confirm { code: "123456" }
   ├── Verificar TOTP con secret
   ├── Activar 2FA (is_enabled = 1)
   └── Mostrar códigos de recuperación
4. Próximo login → pedir código TOTP antes de entrar
```

### Librería recomendada

```bash
composer require pragmarx/google2fa-laravel
```

---

## Audit Log — Consultas Útiles

```php
// Últimos 100 eventos de un usuario
AuditLog::where('user_id', $userId)
    ->latest()
    ->limit(100)
    ->get();

// Todos los logins fallidos de las últimas 24h
AuditLog::where('event', 'login_failed')
    ->where('created_at', '>=', now()->subDay())
    ->get();

// Actividad sospechosa: mismo IP, muchos usuarios fallidos
AuditLog::where('event', 'login_failed')
    ->where('new_values->ip', $suspectIp)
    ->where('created_at', '>=', now()->subHour())
    ->count();
```

```php
// Registro de evento
AuditLog::record(
    event:          'product_key_revealed',
    userId:         $user->id,
    auditableType:  'DigitalKey',
    auditableId:    $key->id,
    newValues:      ['order_id' => $order->id]
);
```

---

## Políticas de Seguridad en Producción

### Checklist antes de ir a producción

- [ ] `APP_ENV=production` `APP_DEBUG=false`
- [ ] `SESSION_SECURE_COOKIE=true` (HTTPS obligatorio)
- [ ] Rotar `APP_KEY` y guardar backup seguro
- [ ] Configurar `MAIL_MAILER=smtp` (no `log`)
- [ ] Activar SSL en Nginx (Let's Encrypt)
- [ ] Agregar header `Strict-Transport-Security`
- [ ] Agregar header `Content-Security-Policy`
- [ ] Configurar firewall: solo puertos 80, 443 y SSH
- [ ] Cerrar puerto MySQL (33060) al exterior
- [ ] Activar `SECURITY_AUDIT_ENABLED=true`
- [ ] Configurar `PAYPAL_MODE=live` y `PAYPAL_WEBHOOK_ID`
- [ ] Cambiar `MERCADOPAGO_ACCESS_TOKEN` de TEST a producción

---

## Middleware de Roles

```php
// app/Http/Middleware/EnsureUserHasRole.php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    if (! $request->user()) {
        return $request->expectsJson()
            ? response()->json(['message' => 'Unauthenticated.'], 401)
            : redirect()->route('login');
    }

    if (! in_array($request->user()->role, $roles)) {
        return $request->expectsJson()
            ? response()->json(['message' => 'Forbidden.'], 403)
            : redirect()->route('home')->with('error', 'Sin permiso para acceder.');
    }

    return $next($request);
}
```

---

## Middleware de Cuenta Activa

```php
// app/Http/Middleware/EnsureUserIsActive.php
public function handle(Request $request, Closure $next): Response
{
    if ($request->user() && ! $request->user()->is_active) {
        Auth::logout();
        $request->session()->invalidate();

        return $request->expectsJson()
            ? response()->json(['message' => 'Account disabled.'], 403)
            : redirect()->route('login')
                ->with('error', 'Tu cuenta ha sido desactivada. Contacta al soporte.');
    }

    return $next($request);
}
```
