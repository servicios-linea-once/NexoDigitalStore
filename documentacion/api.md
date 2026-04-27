# API REST v1 — Para App Flutter

## Configuración Base

| Parámetro | Valor |
|---|---|
| Base URL | `http://localhost:8000/api/v1` |
| Autenticación | Bearer Token (Laravel Sanctum) |
| Formato | JSON |
| Versión | v1 |

---

## Autenticación

### Registro
```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "Juan García",
  "email": "juan@example.com",
  "password": "Secret123",
  "password_confirmation": "Secret123",
  "terms": true
}

Response 201:
{
  "user": { "id": 1, "name": "Juan García", "email": "...", "role": "buyer" },
  "token": "1|abc123xyz...",
  "token_type": "Bearer"
}
```

### Login
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "juan@example.com",
  "password": "Secret123",
  "device_name": "Flutter App"
}

Response 200:
{
  "user": { ... },
  "token": "2|xyz456...",
  "token_type": "Bearer"
}
```

### Logout
```http
POST /api/v1/auth/logout
Authorization: Bearer {token}

Response 200: { "message": "Sesión cerrada" }
```

### Verificar 2FA
```http
POST /api/v1/auth/2fa/verify
Authorization: Bearer {token}

{ "code": "123456" }
```

---

## Catálogo (Público — sin token)

### Listar productos
```http
GET /api/v1/products?q=steam&platform=Steam&region=Global&page=1
GET /api/v1/products?category=juegos&sort=popular&per_page=20

Response 200:
{
  "data": [
    {
      "id": 1,
      "ulid": "01HX...",
      "slug": "cyberpunk-2077-steam",
      "name": "Cyberpunk 2077 — Steam",
      "base_price": 13.99,
      "discount_percent": 30,
      "cashback_percent": 3,
      "stock_count": 45,
      "platform": "Steam",
      "region": "Global",
      "cover_image": "https://res.cloudinary.com/..."
    }
  ],
  "meta": {
    "total": 142,
    "per_page": 20,
    "current_page": 1,
    "last_page": 8
  }
}
```

### Detalle de producto
```http
GET /api/v1/products/{ulid}

Response 200:
{
  "data": {
    "id": 1,
    "name": "...",
    "description": "...",
    "prices": [
      { "currency": "USD", "price": 13.99, "compare_price": 19.99 },
      { "currency": "PEN", "price": 52.46 }
    ],
    "images": [...],
    "seller": { "name": "Línea Once", "rating": 4.9, "verified": true },
    "reviews": [...]
  }
}
```

### Categorías
```http
GET /api/v1/categories           # Solo padres
GET /api/v1/categories/{slug}/children  # Hijos de una categoría
```

---

## Gestión de Licencias (Requiere token)

### Listar mis licencias
```http
GET /api/v1/licenses
Authorization: Bearer {token}

Response 200:
{
  "data": [
    {
      "id": 1,
      "ulid": "01HX...",
      "product_name": "Windows 11 Pro",
      "activation_token": "nxt_...",
      "machine_name": "Mi PC",
      "os_info": "Windows 11 64-bit",
      "is_active": true,
      "activated_at": "2024-01-15T10:30:00Z",
      "expires_at": null
    }
  ]
}
```

### Activar licencia en máquina
```http
POST /api/v1/licenses/{ulid}/activate
Authorization: Bearer {token}

{
  "machine_fingerprint": "sha256-hash-del-hardware",
  "machine_name": "Mi Laptop HP",
  "os_info": "Windows 11 Pro 64-bit",
  "device_type": "laptop"
}

Response 201:
{
  "activation": {
    "activation_token": "nxt_live_abc123...",
    "machine_name": "Mi Laptop HP",
    "expires_at": null
  },
  "message": "Licencia activada correctamente"
}

Error 422: "Has alcanzado el máximo de activaciones para esta licencia"
```

### Desactivar licencia en máquina
```http
DELETE /api/v1/licenses/{ulid}/activations/{activation_id}
Authorization: Bearer {token}

Response 200: { "message": "Licencia desactivada. Ya puedes activarla en otra máquina." }
```

### Heartbeat (verificar licencia activa)
```http
POST /api/v1/licenses/heartbeat
Authorization: Bearer {token}

{
  "activation_token": "nxt_live_abc123...",
  "machine_fingerprint": "sha256-hash"
}

Response 200: { "valid": true, "expires_at": null }
Response 401: { "valid": false, "reason": "License deactivated" }
```

---

## Wallet y NexoTokens (Requiere token)

```http
GET /api/v1/wallet
Response: { "balance": 150.5, "locked": 0, "available": 150.5, "currency": "NT" }

GET /api/v1/wallet/transactions?page=1
Response: { "data": [...transacciones...], "meta": {...} }
```

---

## Suscripciones (Requiere token)

```http
GET /api/v1/subscriptions/plans   # Planes disponibles
GET /api/v1/subscriptions/current # Suscripción activa del usuario
```

---

## Notificaciones (Requiere token)

```http
GET /api/v1/notifications         # No leídas
POST /api/v1/notifications/read-all  # Marcar todas como leídas
```

---

## Códigos de Error

| Código | Significado |
|---|---|
| `200` | OK |
| `201` | Creado |
| `401` | No autenticado |
| `403` | Sin permiso (rol insuficiente) |
| `404` | No encontrado |
| `422` | Error de validación |
| `429` | Rate limit excedido |
| `500` | Error del servidor |

### Formato de error
```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

## Autenticación en Flutter (Ejemplo)

```dart
// Guardar token tras login
final prefs = await SharedPreferences.getInstance();
await prefs.setString('api_token', response['token']);

// Usar en peticiones
final token = prefs.getString('api_token');
final response = await http.get(
  Uri.parse('$baseUrl/api/v1/licenses'),
  headers: {
    'Authorization': 'Bearer $token',
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
);
```
