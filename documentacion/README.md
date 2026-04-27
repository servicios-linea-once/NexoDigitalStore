# NexoKeys — Índice Técnico

**Proyecto:** NexoKeys  
**Stack:** Laravel 13 · Vue 3 · Inertia.js · Tailwind CSS v4 · PrimeVue · Docker  
**Versión:** 1.0.0-beta  

---

## Índice de Documentación

| Archivo | Descripción |
|---|---|
| [arquitectura.md](./arquitectura.md) | Arquitectura general, stack técnico y decisiones de diseño |
| [base-de-datos.md](./base-de-datos.md) | Esquema completo de tablas, relaciones y migraciones |
| [autenticacion.md](./autenticacion.md) | Sistema de autenticación, roles, OAuth y seguridad |
| [perfil.md](./perfil.md) | Perfil de usuario, wallet NT, seguridad, eliminación de cuenta |
| [catalogo.md](./catalogo.md) | Catálogo de productos, filtros, categorías e inventario |
| [economia.md](./economia.md) | NexoTokens, wallet, cashback y transacciones |
| [pagos.md](./pagos.md) | Integración PayPal y MercadoPago Perú |
| [api.md](./api.md) | API REST v1 |
| [telegram.md](./telegram.md) | Bot de Telegram como canal de ventas |
| [cloudinary.md](./cloudinary.md) | Gestión de imágenes con Cloudinary |
| [docker.md](./docker.md) | Entorno Docker, Makefile y despliegue |
| [seguridad.md](./seguridad.md) | Seguridad, 2FA, audit logs y rate limiting |

---

## Cómo usar esta documentación

- Usa el [README raíz](../README.md) para onboarding rápido, setup y comandos de trabajo.
- Usa este índice como puerta de entrada a la documentación técnica y funcional detallada.
- Si un documento no coincide con el código actual, toma el código como fuente de verdad y actualiza la página correspondiente.

## Resumen Ejecutivo

**NexoKeys** es una plataforma de productos digitales con storefront web, panel administrativo y API `v1`. La capa pública actual se apoya en arquitectura `single-vendor`, con la configuración de tienda centralizada en `StoreSetting`.

### Capacidades principales

- Catálogo multi-categoría de productos digitales
- Wallet interna y cashback
- Pagos con PayPal y Mercado Pago
- API `v1` para integraciones y clientes externos
- Seguridad con 2FA, audit logs y rate limiting
- Suscripciones y administración desde panel

## Estado actual conocido

- Arquitectura pública de tienda: `single-vendor`
- Fuente de verdad para datos públicos de tienda: `StoreSetting`
- API disponible y priorizada: `v1`
- Dependencias externas relevantes: PayPal, Mercado Pago, Telegram y Cloudinary
- Áreas con evolución pendiente fuera de esta fase: licencias, wallet/checkout profundo y endurecimiento adicional de tests

## URLs del sistema

| Entorno | URL |
|---|---|
| Desarrollo (Docker) | `http://localhost:8000` |
| API v1 | `http://localhost:8000/api/v1` |
| Adminer (DB) | `http://localhost:8080` *(perfil opcional)* |

## Inicio rápido

```bash
git clone <repo-url> nexokeys
cd nexokeys
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm install
docker compose exec app npm run dev
docker compose exec app npm run build
```
