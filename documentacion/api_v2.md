# 🚀 Nexo Digital Store — API Documentation v2.1

Esta documentación detalla los endpoints de la API, incluyendo el nuevo **Módulo Administrativo** y el sistema de pagos exclusivo por **NexoTokens (NT)**.

## 📌 Información General
- **Base URL:** `/api/v1`
- **Autenticación:** Laravel Sanctum (Bearer Token)
- **Método de Pago Único (API):** NexoTokens (NT)
- **Filtros y Orden:** Soporte nativo para Spatie QueryBuilder (`filter[...]` y `sort`).

---

## 🔐 Autenticación y Perfil

### Iniciar Sesión / Registro
`POST /auth/login` | `POST /auth/register`
- Retorna el Token de acceso y el objeto `UserData`.

### Perfil de Usuario
`GET /profile`
- Retorna estadísticas de compra, balance de billetera y datos personales.

---

## 💰 Sistema de Pagos (NexoTokens)

Para compras vía API, el único método aceptado es el saldo en la billetera virtual (NT).

### 1. Crear Pedido
`POST /orders`
- **Body:** `{ "items": [ { "product_id": 1, "quantity": 1 } ], "currency": "USD" }`
- **Lógica:** El sistema calcula el total en USD, aplica descuentos de suscripción (ej. 20% Plus) y convierte el monto a NT.
- **Respuesta:** Detalle del pedido y campo `payment.required_nt`.

### 2. Procesar Pago
`POST /orders/{ulid}/pay`
- **Acción:** Debita los NT de la billetera del usuario y marca el pedido como completado.
- **Respuesta:** Confirmación de pago y las claves digitales entregadas (si el producto es automático).

---

## 🛠️ Módulo Administrativo (Solo Admin)
*Requiere cabecera `Authorization: Bearer {token}` de un usuario con rol admin.*

### 👥 Gestión de Usuarios
`GET /admin/users`
- **Filtros:** `filter[name]`, `filter[email]`, `filter[role]`.
- **Acciones:** `PATCH /admin/users/{id}/status` (Activar/Suspender).

### 📦 Gestión de Productos
`GET /admin/products` | `POST /admin/products`
- **Soporte de Variantes:** Al crear o editar, se pueden enviar arrays de variantes con precios específicos.
- **Filtros:** `filter[status]`, `filter[category_id]`.

### ⚙️ Ajustes Globales
`GET /admin/settings/general` | `PUT /admin/settings/general`
- Permite cambiar el nombre del sitio, estado de mantenimiento, SEO y Redes Sociales.

---

## 🏷️ Catálogo y Búsqueda

### Búsqueda Global
`GET /search?q={query}`
- Busca en Productos, Usuarios y Categorías simultáneamente.

### Listado de Productos
`GET /products`
- **Parámetros Pro:**
    - `filter[platform]`: Steam, Xbox, etc.
    - `filter[has_stock]`: `yes` o `no`.
    - `sort`: `price_usd`, `-created_at`, `total_sales`.

---

## 📊 Estructuras de Datos (Laravel Data)

### ProductData
```json
{
  "ulid": "string",
  "name": "string",
  "price_usd": 19.99,
  "variants": [
    {
      "variant_name": "OEM Phone",
      "price_usd": 15.50,
      "stock_count": 10
    }
  ],
  "cover_image_url": "https://..."
}
```

---

## 🚀 Reglas de Rendimiento
- **Caché:** Las rutas de catálogo están optimizadas con `ResponseCache`.
- **Media:** Todas las imágenes devueltas por la API están procesadas por Cloudinary (`f_auto, q_auto`).
