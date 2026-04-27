# Base de Datos — Esquema Completo

## Resumen de Tablas (27 tablas)

| # | Tabla | Descripción |
|---|---|---|
| 1 | `users` | Usuarios (compradores, vendedores, admin) |
| 2 | `seller_profiles` | Perfil extendido del vendedor |
| 3 | `wallets` | Wallet NexoTokens por usuario |
| 4 | `wallet_transactions` | Ledger auditado de movimientos NT |
| 5 | `currencies` | Monedas soportadas (USD, PEN, COP, MXN, EUR, NT) |
| 6 | `categories` | Árbol de categorías (padre-hijo) |
| 7 | `products` | Catálogo de productos digitales |
| 8 | `product_images` | Imágenes en Cloudinary por producto |
| 9 | `product_prices` | Precio por moneda por producto |
| 10 | `digital_keys` | Inventario de claves encriptadas |
| 11 | `license_activations` | Activaciones de licencia por máquina |
| 12 | `orders` | Pedidos |
| 13 | `order_items` | Ítems dentro de cada pedido |
| 14 | `payments` | Pagos (PayPal, MercadoPago) |
| 15 | `disputes` | Disputas de comprador vs vendedor |
| 16 | `reviews` | Reseñas de productos |
| 17 | `subscription_plans` | Planes de suscripción disponibles |
| 18 | `user_subscriptions` | Suscripciones activas por usuario |
| 19 | `audit_logs` | Log de eventos de seguridad |
| 20 | `two_factor_auth` | TOTP 2FA por usuario |
| 21 | `telegram_users` | Usuarios vinculados al bot de Telegram |
| 22 | `notifications` | Notificaciones Laravel DB |
| 23 | `jobs` | Cola de trabajos (Redis) |
| 24 | `failed_jobs` | Trabajos fallidos |
| 25 | `cache` | Tabla cache (fallback) |
| 26 | `personal_access_tokens` | Sanctum tokens para API Flutter |
| 27 | `password_reset_tokens` | Tokens de recuperación de contraseña |

---

## Esquema Detallado

### `users`
```sql
id              BIGINT UNSIGNED  PK AUTO_INCREMENT
ulid            CHAR(26)         UNIQUE NOT NULL
name            VARCHAR(255)     NOT NULL
username        VARCHAR(50)      UNIQUE NOT NULL
email           VARCHAR(255)     UNIQUE NOT NULL
email_verified_at TIMESTAMP      NULLABLE
password        VARCHAR(255)     NOT NULL (hashed)
avatar          TEXT             NULLABLE (URL Cloudinary o OAuth)
role            ENUM(buyer, seller, admin)  DEFAULT buyer
provider        VARCHAR(50)      NULLABLE (google, steam)
provider_id     VARCHAR(255)     NULLABLE
is_active       TINYINT(1)       DEFAULT 1
last_login_at   TIMESTAMP        NULLABLE
remember_token  VARCHAR(100)     NULLABLE
created_at, updated_at
```

### `seller_profiles`
```sql
id              BIGINT UNSIGNED  PK
user_id         BIGINT UNSIGNED  FK → users.id (UNIQUE)
store_name      VARCHAR(150)     NOT NULL
store_slug      VARCHAR(150)     UNIQUE NOT NULL
description     TEXT             NULLABLE
avatar          TEXT             NULLABLE (Cloudinary)
banner          TEXT             NULLABLE (Cloudinary)
commission_rate DECIMAL(5,2)     DEFAULT 5.00 (%)
balance         DECIMAL(12,4)    DEFAULT 0.00 (USD)
kyc_status      ENUM(pending, approved, rejected) DEFAULT pending
kyc_documents   JSON             NULLABLE
is_verified     TINYINT(1)       DEFAULT 0
rating          DECIMAL(3,2)     DEFAULT 0.00
total_sales     BIGINT           DEFAULT 0
total_revenue   DECIMAL(14,4)    DEFAULT 0.00
telegram_id     VARCHAR(100)     NULLABLE
created_at, updated_at
```

### `wallets`
```sql
id              BIGINT UNSIGNED  PK
ulid            CHAR(26)         UNIQUE
user_id         BIGINT UNSIGNED  FK → users.id (UNIQUE)
balance         DECIMAL(16,4)    DEFAULT 0.0000
locked_balance  DECIMAL(16,4)    DEFAULT 0.0000
currency        VARCHAR(10)      DEFAULT 'NT'
created_at, updated_at
```

> **Nota:** `available_balance = balance - locked_balance`

### `wallet_transactions`
```sql
id              BIGINT UNSIGNED  PK
ulid            CHAR(26)         UNIQUE
wallet_id       BIGINT UNSIGNED  FK → wallets.id
user_id         BIGINT UNSIGNED  FK → users.id
type            ENUM(credit, debit, lock, unlock, refund, cashback, purchase)
amount          DECIMAL(16,4)    NOT NULL
balance_after   DECIMAL(16,4)    NOT NULL
reason          VARCHAR(255)     NULLABLE
reference_type  VARCHAR(100)     NULLABLE (Order, Payment, etc.)
reference_id    BIGINT UNSIGNED  NULLABLE
created_at      TIMESTAMP        (inmutable — no updated_at)
```

### `currencies`
```sql
id          BIGINT UNSIGNED  PK
code        VARCHAR(10)      UNIQUE (USD, PEN, COP, MXN, EUR, NT)
name        VARCHAR(100)
symbol      VARCHAR(10)
rate_to_usd DECIMAL(12,6)    (1 NT = 0.100000)
is_active   TINYINT(1)       DEFAULT 1
is_default  TINYINT(1)       DEFAULT 0 (solo USD = 1)
created_at, updated_at
```

### `categories`
```sql
id          BIGINT UNSIGNED  PK
parent_id   BIGINT UNSIGNED  FK → categories.id NULLABLE
name        VARCHAR(150)     NOT NULL
slug        VARCHAR(150)     UNIQUE NOT NULL
description TEXT             NULLABLE
icon        VARCHAR(100)     NULLABLE (clase PrimeIcons: "pi pi-desktop")
image       TEXT             NULLABLE (Cloudinary)
color       VARCHAR(20)      NULLABLE (hex color)
is_active   TINYINT(1)       DEFAULT 1
is_featured TINYINT(1)       DEFAULT 0
sort_order  SMALLINT         DEFAULT 0
created_at, updated_at
```

### `products`
```sql
id                    BIGINT UNSIGNED  PK
ulid                  CHAR(26)         UNIQUE
seller_id             BIGINT UNSIGNED  FK → users.id
category_id           BIGINT UNSIGNED  FK → categories.id
name                  VARCHAR(255)     NOT NULL
slug                  VARCHAR(255)     UNIQUE NOT NULL
description           TEXT             NULLABLE
short_description     VARCHAR(500)     NULLABLE
cover_image           TEXT             NULLABLE  ← URL principal (legacy)
platform              VARCHAR(100)     NULLABLE (Steam, Netflix, etc.)
region                VARCHAR(50)      NULLABLE (Global, PE, US, EU, MX, CO)
delivery_type         ENUM(automatic, manual, api)  DEFAULT automatic
status                ENUM(draft, active, paused, archived)  DEFAULT draft
base_price            DECIMAL(12,4)    NOT NULL (USD)
base_currency         VARCHAR(10)      DEFAULT 'USD'
discount_percent      DECIMAL(5,2)     DEFAULT 0.00
cashback_percent      DECIMAL(5,2)     DEFAULT 0.00
stock_count           INT              DEFAULT 0
is_featured           TINYINT(1)       DEFAULT 0
is_preorder           TINYINT(1)       DEFAULT 0
preorder_release_date DATE             NULLABLE
total_sales           BIGINT           DEFAULT 0
rating                DECIMAL(3,2)     DEFAULT 0.00
rating_count          INT              DEFAULT 0
activation_guide      TEXT             NULLABLE
delivery_info         TEXT             NULLABLE
tos                   TEXT             NULLABLE
tags                  JSON             NULLABLE
meta                  JSON             NULLABLE
deleted_at            TIMESTAMP        NULLABLE (SoftDeletes)
created_at, updated_at
FULLTEXT INDEX (name, description)
```

### `product_images`
```sql
id          BIGINT UNSIGNED  PK
product_id  BIGINT UNSIGNED  FK → products.id
url         TEXT             NOT NULL (Cloudinary URL)
public_id   VARCHAR(255)     NULLABLE (Cloudinary public_id para delete)
alt_text    VARCHAR(255)     NULLABLE
sort_order  SMALLINT         DEFAULT 0
is_cover    TINYINT(1)       DEFAULT 0
created_at, updated_at
```

### `product_prices`
```sql
id            BIGINT UNSIGNED  PK
product_id    BIGINT UNSIGNED  FK → products.id
currency_code VARCHAR(10)      NOT NULL (USD, PEN, etc.)
price         DECIMAL(12,4)    NOT NULL
compare_price DECIMAL(12,4)    NULLABLE (precio tachado)
created_at, updated_at
UNIQUE (product_id, currency_code)
```

### `digital_keys`
```sql
id                  BIGINT UNSIGNED  PK
ulid                CHAR(26)         UNIQUE
product_id          BIGINT UNSIGNED  FK → products.id
seller_id           BIGINT UNSIGNED  FK → users.id
key_value           TEXT             NOT NULL (ENCRIPTADO — Laravel encrypt())
status              ENUM(available, reserved, sold, cancelled, refunded)
order_item_id       BIGINT UNSIGNED  FK → order_items.id NULLABLE (diferida)
reserved_at         TIMESTAMP        NULLABLE
reserved_until      TIMESTAMP        NULLABLE (expiración de reserva)
sold_at             TIMESTAMP        NULLABLE
delivery_method     ENUM(automatic, manual, api)  DEFAULT automatic
max_activations     TINYINT          DEFAULT 1
current_activations TINYINT          DEFAULT 0
license_type        ENUM(perpetual, subscription, trial)  DEFAULT perpetual
license_expires_at  TIMESTAMP        NULLABLE
created_at, updated_at
```

### `license_activations`
```sql
id                 BIGINT UNSIGNED  PK
ulid               CHAR(26)         UNIQUE
digital_key_id     BIGINT UNSIGNED  FK → digital_keys.id
user_id            BIGINT UNSIGNED  FK → users.id
order_item_id      BIGINT UNSIGNED  FK → order_items.id NULLABLE
machine_fingerprint VARCHAR(255)    NOT NULL (hash del hardware)
machine_name       VARCHAR(255)     NULLABLE
os_info            VARCHAR(255)     NULLABLE
device_type        ENUM(desktop, laptop, mobile, vm) DEFAULT desktop
activation_token   VARCHAR(255)     UNIQUE (token para heartbeat API Flutter)
is_active          TINYINT(1)       DEFAULT 1
activated_at       TIMESTAMP
expires_at         TIMESTAMP        NULLABLE
last_heartbeat_at  TIMESTAMP        NULLABLE
deactivated_at     TIMESTAMP        NULLABLE
deactivation_reason VARCHAR(255)    NULLABLE
created_at, updated_at
```

### `orders`
```sql
id                BIGINT UNSIGNED  PK
ulid              CHAR(26)         UNIQUE
buyer_id          BIGINT UNSIGNED  FK → users.id
status            ENUM(pending, processing, completed, failed, refunded, disputed)
currency          VARCHAR(10)      NOT NULL (moneda de la compra)
subtotal          DECIMAL(12,4)
discount_amount   DECIMAL(12,4)    DEFAULT 0
nexocoins_used    DECIMAL(12,4)    DEFAULT 0 (NT gastados)
total             DECIMAL(12,4)
subtotal_usd      DECIMAL(12,4)
total_usd         DECIMAL(12,4)
exchange_rate     DECIMAL(12,6)    (tasa al momento de la compra)
payment_method    ENUM(paypal, mercadopago, nexotokens, mixed)
payment_reference VARCHAR(255)     NULLABLE (PayPal/MP transaction ID)
notes             TEXT             NULLABLE
ip_address        VARCHAR(50)      NULLABLE
completed_at      TIMESTAMP        NULLABLE
created_at, updated_at
```

### `order_items`
```sql
id               BIGINT UNSIGNED  PK
ulid             CHAR(26)         UNIQUE
order_id         BIGINT UNSIGNED  FK → orders.id
product_id       BIGINT UNSIGNED  FK → products.id NULLABLE
seller_id        BIGINT UNSIGNED  FK → users.id NULLABLE
product_name     VARCHAR(255)     NOT NULL (snapshot del nombre)
product_platform VARCHAR(100)     NULLABLE
product_region   VARCHAR(50)      NULLABLE
unit_price       DECIMAL(12,4)
unit_price_usd   DECIMAL(12,4)
quantity         INT              DEFAULT 1
subtotal         DECIMAL(12,4)
commission_rate  DECIMAL(5,2)     (% al momento de la compra)
commission_amount DECIMAL(12,4)
seller_earnings  DECIMAL(12,4)
cashback_nt      DECIMAL(12,4)    DEFAULT 0 (NT otorgados al comprador)
delivery_status  ENUM(pending, delivered, failed, disputed)
delivered_at     TIMESTAMP        NULLABLE
digital_key_id   BIGINT UNSIGNED  FK → digital_keys.id NULLABLE
created_at, updated_at
```

### `payments`
```sql
id              BIGINT UNSIGNED  PK
ulid            CHAR(26)         UNIQUE
order_id        BIGINT UNSIGNED  FK → orders.id
gateway         ENUM(paypal, mercadopago, nexotokens)
gateway_id      VARCHAR(255)     UNIQUE (transaction ID externo)
gateway_status  VARCHAR(100)     (COMPLETED, approved, etc.)
amount          DECIMAL(12,4)
currency        VARCHAR(10)
amount_usd      DECIMAL(12,4)
exchange_rate   DECIMAL(12,6)
status          ENUM(pending, completed, failed, refunded, cancelled)
payer_email     VARCHAR(255)     NULLABLE
payer_id        VARCHAR(255)     NULLABLE
raw_response    JSON             NULLABLE (respuesta completa del gateway)
webhook_verified TINYINT(1)      DEFAULT 0
completed_at    TIMESTAMP        NULLABLE
created_at, updated_at
```

### `audit_logs`
```sql
id              BIGINT UNSIGNED  PK
user_id         BIGINT UNSIGNED  FK → users.id NULLABLE
event           VARCHAR(100)     NOT NULL (login_success, registered, etc.)
auditable_type  VARCHAR(255)     NULLABLE
auditable_id    BIGINT UNSIGNED  NULLABLE
old_values      JSON             NULLABLE
new_values      JSON             NULLABLE
ip_address      VARCHAR(50)      NULLABLE
user_agent      TEXT             NULLABLE
created_at      TIMESTAMP        (inmutable — sin updated_at)
```

---

## Relaciones Principales

```
users ─────1──< seller_profiles
users ─────1──< wallets ──1──< wallet_transactions
users ─────*──< orders ──1──< order_items ──1──< digital_keys
users ─────*──< reviews
users ─────*──< user_subscriptions ──*──1 subscription_plans
users ─────1──< two_factor_auth
users ─────1──< telegram_users
users ─────*──< license_activations
users ─────*──< audit_logs
categories ──1──< categories (self-referential parent/children)
categories ──1──< products
products ───1──< product_images
products ───1──< product_prices
products ───1──< digital_keys
products ───1──< reviews
orders ─────1──< payments
orders ─────1──< disputes
```

---

## Índices Importantes

```sql
-- Búsqueda de texto completo
FULLTEXT INDEX products_fulltext (name, description)

-- Reservas expiradas (job scheduled)
INDEX digital_keys_reserved_until_idx (reserved_until)
INDEX digital_keys_status_idx (status)

-- Listado de productos por vendedor
INDEX products_seller_id_status_idx (seller_id, status)

-- Activaciones de licencia
UNIQUE INDEX license_activations_machine_key (digital_key_id, machine_fingerprint)
UNIQUE INDEX license_activations_token (activation_token)

-- Pagos por gateway
UNIQUE INDEX payments_gateway_id (gateway_id)
```
