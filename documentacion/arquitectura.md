# Arquitectura del Sistema

## Stack Técnico

| Capa | Tecnología | Versión | Rol |
|---|---|---|---|
| Backend | Laravel | 13.x | API + SSR via Inertia |
| Frontend | Vue.js | 3.x | SPA dentro de Inertia |
| Bridge | Inertia.js | 3.x | Elimina necesidad de API separada para el frontend |
| CSS | Tailwind CSS | v4 | Utilidades + design system propio |
| UI Components | PrimeVue | 4.x (Aura Dark) | Componentes premium |
| Rutas en frontend | Ziggy | 2.x | Rutas Laravel en Vue |
| Base de datos | MySQL | 8.0 | Almacenamiento principal |
| Caché / Queue | Redis | 7 | Caché, colas de trabajos |
| Imágenes | Cloudinary | 3.x | CDN + transformaciones |
| Servidor web | Nginx | 1.27 | Proxy reverso + assets estáticos |
| PHP runtime | PHP-FPM | 8.4 | Procesamiento de peticiones |
| Contenedores | Docker Compose | 2.x | Entorno reproducible |

---

## Diagrama de Arquitectura

```
Browser (Vue 3 + Inertia + PrimeVue)
        │
      HTTP
        │
  Nginx 1.27 (Docker :8000)
  ├── /public/build  → assets estáticos (Vite)
  └── *.php          → PHP-FPM 8.4
                            │
                     Laravel 13
                     ├── Routes (web + api)
                     ├── Controllers
                     ├── Models (Eloquent)
                     └── Inertia responses
                            │
              ┌─────────────┴──────────────┐
           MySQL 8                      Redis 7
           (Docker)              ├── Cache (nav, products)
                                 └── Queue (emails, jobs)

       Servicios Externos
  ┌──────────┬──────────┬──────────┐
  │Cloudinary│  PayPal  │ MercadoPago│
  └──────────┴──────────┴──────────┘
                   │
            Bot Telegram (ventas)
```

---

## Estructura de Directorios

```
nexokeys/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/               # Login, Register, Password, EmailVerification
│   │   │   ├── Api/V1/             # API REST para Flutter
│   │   │   ├── Webhook/            # PayPal, MercadoPago, Telegram
│   │   │   ├── HomeController.php
│   │   │   └── ProductController.php
│   │   └── Middleware/
│   │       ├── HandleInertiaRequests.php  # Props globales compartidas
│   │       ├── EnsureUserHasRole.php      # Roles (admin/seller/buyer)
│   │       └── EnsureUserIsActive.php     # Bloqueo de cuentas
│   ├── Models/                     # Eloquent models
│   ├── Services/
│   │   └── CloudinaryService.php   # Upload/delete/transform imágenes
│   └── Notifications/
│       └── WelcomeNotification.php # Email bienvenida (queued)
│
├── config/
│   ├── nexo.php        # Config central: NT, pagos, comisiones, seguridad
│   ├── cloudinary.php  # Transformaciones por tipo de recurso
│   └── services.php    # OAuth Google + Steam
│
├── database/
│   ├── migrations/     # 27 tablas
│   └── seeders/
│       ├── CurrencySeeder.php   # 6 monedas
│       └── CategorySeeder.php   # 6 padre + 35 hijos
│
├── resources/
│   ├── css/app.css          # Design system Tailwind v4
│   └── js/
│       ├── app.js            # Bootstrap Vue + Inertia + PrimeVue
│       ├── Layouts/          # AppLayout + AuthLayout
│       ├── Pages/            # Home, Auth, Products
│       └── Components/       # ProductCard, Alert
│
├── routes/
│   ├── web.php    # Public, auth, buyer, seller, admin, webhooks
│   └── api.php    # API v1 Sanctum para Flutter
│
├── docker/        # Dockerfile, Nginx, MySQL config
├── docker-compose.yml
├── Makefile       # make up | migrate | shell | seed
└── documentacion/ # Esta carpeta
```

---

## Flujo de una Petición

```
1. Browser → GET /products?q=steam
2. Nginx → PHP-FPM (app:9000)
3. Laravel Router → ProductController@index
4. Middleware → HandleInertiaRequests (share: auth, flash, navCategories)
5. Controller aplica filtros → paginación 24/página
6. Inertia::render('Products/Index', [...props])
   - Primer visit  → HTML completo + JSON embebido
   - Navegación SPA → solo JSON props
7. Vue 3 → hydrate/mount el componente
```

---

## Decisiones de Diseño

| Decisión | Por qué |
|---|---|
| Inertia en lugar de API + SPA separado | Sin CORS, auth por sesiones, un solo repo |
| `predis` en lugar de `phpredis` | Sin race condition DNS en Docker |
| ULID en lugar de UUID/autoincrement | Ordenable, más corto, no revela secuencias |
| Solo Línea Once y AGX pueden vender | Modelo cerrado, KYC manual, antifraud |
| PayPal + MercadoPago (sin Stripe) | Cobertura Latinoamérica, prioridad Perú |
| NexoTokens (NT) como moneda interna | Fidelización, cashback, reduce fricción de pago |
| Cloudinary para imágenes | CDN global, transformaciones en el fly, sin servidor propio |
