# Perfil de Usuario

## Rutas disponibles

| Método | URI | Nombre | Descripción |
|--------|-----|---------|-------------|
| `GET`    | `/profile`           | `profile.index`           | Dashboard del perfil |
| `PUT`    | `/profile`           | `profile.update`          | Actualizar nombre y avatar |
| `DELETE` | `/profile`           | `profile.destroy`         | Eliminar cuenta (requiere password) |
| `GET`    | `/profile/security`  | `profile.security`        | Seguridad: password, 2FA, OAuth |
| `PUT`    | `/profile/password`  | `profile.password.update` | Cambiar contraseña |
| `GET`    | `/profile/wallet`    | `profile.wallet`          | Wallet NT con historial |

> **Middleware:** `auth` + `verified` — se requiere email verificado.

---

## Páginas Vue

### `Profile/Index.vue` — Mi Perfil
El dashboard principal del usuario:

- **Sidebar**: Avatar (con upload inline), nombre, email, badge de rol, saldo NT, estadísticas (pedidos/gastado), navegación entre secciones
- **Formulario de edición**: Nombre + avatar (preview antes de subir)
- **Pedidos recientes**: Últimos 5 pedidos con estado y total, enlace a historial completo

### `Profile/Security.vue` — Seguridad
- **Cambiar contraseña**: Formulario con barra de fortaleza (4 niveles), toggle de visibilidad, validación de coincidencia en tiempo real
- **Cuentas vinculadas**: Estado de Google OAuth y Steam OAuth con botón de vinculación
- **2FA**: Estado activo/inactivo, enlace al setup TOTP
- **Actividad reciente**: Últimos 20 registros de auditoría con iconos por tipo de acción
- **Eliminar cuenta**: Botón con modal de confirmación que requiere contraseña

### `Profile/Wallet.vue` — Mi Wallet NT
- **Hero card**: Saldo NT disponible + equivalente USD (1 NT = $0.10), balance bloqueado
- **Explainer**: Tarjetas de cómo ganar, usar y la seguridad de los NT
- **Historial de transacciones**: Paginado, filtrable por tipo (cashback, debit, refund, credit)
  - Tipos de transacción con íconos y colores diferenciados
  - Monto con signo positivo/negativo

---

## Controlador: `ProfileController`

```php
// GET /profile → index()
// Retorna: user con wallet, recentOrders (5), stats{totalOrders, totalSpent, ntBalance, ntLocked}

// PUT /profile → update()
// Input: name (required), avatar (optional image, max 2MB)
// Acción: actualiza user, almacena avatar en storage/avatars

// GET /profile/security → security()
// Retorna: user, auditLogs (20), has2fa, linkedGoogle, linkedSteam

// GET /profile/wallet → wallet()
// Retorna: wallet, transactions (paginado 20)

// DELETE /profile → destroy()
// Input: password (current_password)
// Acción: logout, soft-delete user, invalida sesión
```

---

## Flujo de cambio de contraseña

```
PUT /profile/password (PasswordController@update)
  ├── Validar: current_password (current_password rule), password (min:8, confirmed)
  ├── Hash::make(new password)
  ├── User::update(['password' => ...])
  ├── AuditLog::record('password_changed')
  └── Redirect back with success
```

---

## Flujo de upload de avatar

```
PUT /profile (ProfileController@update)
  ├── Validar: name (required), avatar (nullable|image|max:2048)
  ├── Si hay avatar:
  │   └── store('avatars', 'public') → genera URL pública
  ├── User::update(['name', 'avatar'])
  └── AuditLog::record('profile_updated')
```

> **En producción**: Reemplazar `Storage::disk('public')` con `CloudinaryService::upload()` para imágenes de avatar en la nube.

---

## Flujo de eliminación de cuenta

```
DELETE /profile (ProfileController@destroy)
  ├── Validar: password (current_password)
  ├── AuditLog::record('account_deleted')
  ├── auth()->logout()
  ├── User::delete() (soft delete)
  └── session()->invalidate() → redirect '/'
```
