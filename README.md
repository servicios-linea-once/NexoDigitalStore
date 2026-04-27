# NexoKeys

NexoKeys es una tienda de productos digitales construida con Laravel 13, Vue 3 e Inertia. El proyecto combina storefront web, panel administrativo, API `v1` para clientes externos o app móvil, wallet interna, suscripciones, reseñas y flujos de pago con PayPal y Mercado Pago.

## Stack

- Backend: Laravel 13, PHP 8.3, Inertia.js, Sanctum, Spatie Permission
- Frontend: Vue 3, Vite, PrimeVue, Tailwind CSS 4, Vue I18n
- Datos e integraciones: MySQL/SQLite para tests, Redis opcional, Cloudinary, Telegram, PayPal, Mercado Pago
- Tooling: Docker Compose, Makefile, PHPUnit

## Módulos principales

- Catálogo de productos digitales con categorías, promociones y wishlist
- Checkout y órdenes con entrega de claves digitales
- Wallet de NexoTokens y cashback
- Suscripciones y solicitudes de suscripción
- Perfil, sesiones y autenticación con 2FA
- Panel admin para usuarios, roles, store settings, productos, órdenes y reseñas
- API `v1` para auth, productos, órdenes, licencias, wallet y perfil

## Inicio rápido

### Opción Docker

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
npm install
npm run build
```

### Opción local

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
php artisan serve
```

## Comandos útiles

```bash
composer test
php artisan test
npm run build
composer dev
php artisan optimize:clear
```

## Rutas principales

- Web: `/`
- Admin: `/admin/dashboard`
- API: `/api/v1`
- Healthcheck: `/up`

## Testing

La base de tests usa `RefreshDatabase` para las suites smoke críticas. Las pruebas priorizadas en esta fase cubren auth API, product API y carga básica de home.

```bash
php artisan test tests/Feature/Api/V1/AuthApiTest.php
php artisan test tests/Feature/Api/V1/ProductApiTest.php
php artisan test tests/Feature/ExampleTest.php
```

## Documentación

La documentación técnica detallada vive en [documentacion/README.md](./documentacion/README.md). Ahí están arquitectura, base de datos, pagos, seguridad, catálogo, Docker y API.

## Estado actual conocido

- La capa pública de tienda opera con arquitectura `single-vendor`
- `StoreSetting` es la fuente de verdad para metadatos públicos de tienda
- La API pública priorizada en esta fase es `v1`
- Los flujos de pago y webhooks dependen de configuración externa válida en `.env`
- Hay módulos avanzados fuera de esta fase que todavía requieren endurecimiento adicional, especialmente licencias y wallet/checkout profundo
