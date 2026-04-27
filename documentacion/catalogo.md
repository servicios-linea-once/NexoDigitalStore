# Catálogo de Productos

## Categorías

### Estructura jerárquica (2 niveles)

```
Juegos (parent)
├── Steam
├── Epic Games
├── PlayStation
├── Xbox
├── Nintendo
├── GOG
├── Battle.net
└── Rockstar

Gift Cards (parent)
├── Amazon Gift Card
├── Google Play
├── iTunes / App Store
├── Steam Wallet
├── PSN Gift Card
└── Xbox Gift Card

Software (parent)
├── Windows
├── Microsoft Office
├── Antivirus
└── Diseño

Streaming (parent)
├── Netflix
├── Spotify
├── Disney+
├── YouTube Premium
├── HBO Max
└── Crunchyroll

IA & Herramientas (parent)
├── ChatGPT Plus
├── Midjourney
├── Adobe CC
└── Canva Pro

Cuentas (parent)
└── (sin hijos iniciales)
```

### Modelo `Category`

```php
Category::where('slug', $slug)->first()
Category::where('is_active', true)->whereNull('parent_id')->get() // padres
Category::where('parent_id', $id)->get()                          // hijos

// Árbol completo para sidebar con hijos:
$categories = Category::with('children')
    ->whereNull('parent_id')
    ->where('is_active', true)
    ->orderBy('sort_order')
    ->get();
```

---

## Productos

### Estados de un Producto

| Estado | Descripción |
|---|---|
| `draft` | Borrador, no visible en el catálogo |
| `active` | Visible y disponible para compra |
| `paused` | Visible pero no comprable |
| `archived` | Archivado, no visible |

### Tipos de Entrega (`delivery_type`)

| Tipo | Descripción |
|---|---|
| `automatic` | La clave se entrega inmediatamente al pagar |
| `manual` | El vendedor envía la clave manualmente (email/chat) |
| `api` | La clave viene de una API externa del vendedor |

### Plataformas Soportadas

Steam · Epic Games · GOG · Battle.net · PSN · Xbox · Nintendo · Netflix · Spotify · Disney+ · Microsoft · Rockstar · YouTube · HBO · Crunchyroll · Amazon · Google Play · Apple

### Regiones

| Código | Label |
|---|---|
| `Global` | 🌍 Global |
| `PE` | 🇵🇪 Perú |
| `US` | 🇺🇸 Estados Unidos |
| `EU` | 🇪🇺 Europa |
| `MX` | 🇲🇽 México |
| `CO` | 🇨🇴 Colombia |
| `AR` | 🇦🇷 Argentina |

---

## ProductController — Endpoints Web

### `GET /products` — Catálogo con filtros

**Parámetros de query:**

| Parámetro | Tipo | Descripción |
|---|---|---|
| `q` | string | Búsqueda en nombre y descripción |
| `category` | string | Slug de categoría (busca también en hijos) |
| `platform` | string | Plataforma (Steam, PSN, etc.) |
| `region` | string | Región (Global, PE, US, etc.) |
| `price_min` | number | Precio mínimo en USD |
| `price_max` | number | Precio máximo en USD |
| `in_stock` | boolean | Solo productos con stock > 0 |
| `sort` | string | `newest` `popular` `rating` `price_asc` `price_desc` |
| `page` | int | Página (paginación 24/página) |

**Respuesta Inertia:**
```json
{
  "products": {
    "data": [...],
    "total": 142,
    "per_page": 24,
    "current_page": 1,
    "last_page": 6
  },
  "categories": [...],
  "filters": { "q": "steam", "platform": "Steam" }
}
```

### `GET /products/{slug}` — Detalle de producto

**Props Inertia:**
```json
{
  "product": {
    "id": 1,
    "slug": "cyberpunk-2077-steam",
    "name": "Cyberpunk 2077 — Clave Steam",
    "base_price": 13.99,
    "discount_percent": 30,
    "cashback_percent": 3,
    "stock_count": 45,
    "rating": 4.7,
    "rating_count": 128,
    "platform": "Steam",
    "region": "Global",
    "cover_image": "https://res.cloudinary.com/...",
    "images": [{ "id": 1, "url": "...", "is_cover": true }],
    "prices": [
      { "currency": "USD", "price": 13.99, "compare_price": 19.99 },
      { "currency": "PEN", "price": 52.46, "compare_price": null }
    ],
    "seller": {
      "name": "Línea Once",
      "rating": 4.9,
      "sales": 12450,
      "verified": true
    },
    "reviews": [...]
  },
  "related": [...]
}
```

### `GET /category/{slug}` — Por categoría

Mismo formato que `/products` con `filters.category` pre-establecido.

---

## Inventario de Claves Digitales

### Estados de una Clave

```
available → reserved → sold
    │              │
    └── cancelled  └── refunded
```

### Flujo de Compra (Reserva Pesimista)

```php
// 1. Reservar clave (lockForUpdate previene condiciones de carrera)
DB::transaction(function () use ($product, $order) {
    $key = DigitalKey::where('product_id', $product->id)
        ->where('status', 'available')
        ->lockForUpdate()       // Bloqueo pesimista a nivel de fila
        ->first();

    if (! $key) throw new \Exception('Sin stock');

    $key->update([
        'status'         => 'reserved',
        'reserved_at'    => now(),
        'reserved_until' => now()->addMinutes(15),
    ]);

    // Descontar stock del producto
    $product->decrement('stock_count');
});

// 2. Al completar el pago → marcar como vendida
$key->update([
    'status'         => 'sold',
    'order_item_id'  => $orderItem->id,
    'sold_at'        => now(),
]);

// 3. Liberar reservas expiradas (Scheduler)
// App\Console\Kernel → cada minuto
DigitalKey::expiredReservations()->update([
    'status'         => 'available',
    'reserved_at'    => null,
    'reserved_until' => null,
]);
```

---

## Imágenes de Productos (Cloudinary)

Cada producto tiene múltiples imágenes en `product_images`. La imagen de portada tiene `is_cover = 1`.

### Transformaciones automáticas

| Uso | Transformación |
|---|---|
| Thumbnail (card) | `w_400, h_300, c_fill, q_auto, f_auto` |
| Cover (detalle) | `w_800, h_600, c_fill, q_auto, f_auto` |
| Galería | `w_1200, q_auto, f_auto` |

### Carpetas en Cloudinary

```
nexo-digital-store/
├── products/    # Imágenes de productos
├── avatars/     # Fotos de perfil de usuarios
├── banners/     # Banners de sellers
├── categories/  # Imágenes de categorías
└── kyc/         # Documentos KYC (private delivery)
```

---

## Precios Multi-moneda

Cada producto tiene un `base_price` en USD y opcionalmente precios específicos en otras monedas en la tabla `product_prices`.

### Lógica de precio para el frontend

```javascript
// En ProductCard.vue, el precio se muestra según la moneda seleccionada:
// 1. Si hay precio específico para esa moneda → usar ese
// 2. Si no → convertir base_price USD usando rate_to_usd de currencies

const price = props.prices.find(p => p.currency === selectedCurrency)?.price
  ?? (basePrice * currencyRate);
```

### Cashback en NT

```
cashback_nt = total_usd * (cashback_percent / 100) / NT_RATE_USD
// Ejemplo: $10 compra con 5% cashback → 5 NT
// (10 * 0.05) / 0.10 = 5 NT
```
