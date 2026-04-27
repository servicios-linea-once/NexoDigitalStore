# Docker — Entorno de Desarrollo

## Servicios

| Servicio | Imagen | Puerto | Descripción |
|---|---|---|---|
| `app` | nexokeys-app (PHP 8.4-FPM) | 9000 (interno) | Laravel app |
| `nginx` | nginx:1.27-alpine | `8000:80` | Servidor web |
| `db` | mysql:8.0 | `33060:3306` | Base de datos |
| `redis` | redis:7-alpine | `6379:6379` | Caché y colas |
| `queue` | nexokeys-queue | — | Worker de colas |
| `scheduler` | nexokeys-scheduler | — | Cron de Laravel |

> Puerto externo MySQL es `33060` para evitar conflicto con MySQL local.

---

## Comandos Rápidos (Makefile)

```bash
# Ciclo de vida
make up       # docker compose up -d --build
make down     # docker compose down
make restart  # docker compose restart

# Laravel
make shell    # Entrar al contenedor app (bash)
make migrate  # php artisan migrate
make seed     # php artisan db:seed
make fresh    # php artisan migrate:fresh --seed
make artisan cmd="make:model Foo"  # Cualquier comando artisan

# Frontend
make dev      # npm run dev (modo dev con hot reload)
make build    # npm run build

# Logs
make logs           # todos los servicios
make logs-app       # solo la app PHP
make logs-nginx     # solo Nginx

# Base de datos
make db-backup      # mysqldump → backups/
make db-restore     # restaurar backup

# Tests
make test           # php artisan test
```

---

## Estructura Docker

```
docker/
├── php/
│   ├── Dockerfile    # Multi-stage: builder (Node) + runtime (PHP-FPM)
│   └── local.ini     # PHP config: 256MB, OPcache, max upload 20MB
├── nginx/
│   └── default.conf  # Virtual host + security headers + gzip
└── mysql/
    └── my.cnf        # utf8mb4, InnoDB, slow query log
```

---

## Dockerfile (PHP 8.4-FPM Alpine)

### Etapas:

1. **Base** — PHP 8.4-FPM Alpine con extensiones:
   - `pdo_mysql` — conexión MySQL
   - `redis` — Predis (pure PHP, sin extensión nativa)
   - `gd` — procesamiento de imágenes
   - `intl` — internacionalización
   - `zip` — archivos comprimidos
   - `pcntl` — señales para colas
   - `opcache` — caché de bytecode PHP

2. **Dev** — Agrega Node.js 20 + npm para compilar assets

3. **Production** — Composer install sin dev, `composer dump-autoload -o`

---

## Variables de Entorno Clave

```env
# Docker hostnames (no usar 127.0.0.1)
DB_HOST=db          # nombre del servicio en docker-compose
REDIS_HOST=redis    # nombre del servicio en docker-compose

# Redis — usar predis (pure PHP)
REDIS_CLIENT=predis

# Sesiones — 'file' en dev para evitar race conditions
SESSION_DRIVER=file

# Cache — Redis
CACHE_STORE=redis
CACHE_PREFIX=nexo_

# Queue — Redis
QUEUE_CONNECTION=redis
```

---

## Healthchecks

```yaml
# MySQL — espera hasta que acepta conexiones
healthcheck:
  test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
  interval: 10s
  timeout: 5s
  retries: 5
  start_period: 30s   # Da tiempo de arrancar MySQL

# Redis — ping simple
healthcheck:
  test: ["CMD", "redis-cli", "ping"]
  interval: 10s
  timeout: 5s
  retries: 3
```

---

## Redes

Todos los servicios están en la red `nexo_net` (bridge driver). Esto permite:
- Resolución DNS por nombre: `db`, `redis`, `app`
- Aislamiento del host
- Comunicación interna sin exponer puertos

---

## Problemas Comunes

### `getaddrinfo for redis failed`
**Causa:** `REDIS_CLIENT=phpredis` con race condition DNS  
**Solución:** Usar `REDIS_CLIENT=predis` (ya configurado)

### Puerto 3306 ya en uso
**Causa:** MySQL local corriendo en el host  
**Solución:** El puerto externo está en `33060:3306` (ya configurado)

### `invalid file request public/storage`
**Causa:** El symlink `public/storage` no es compatible con Docker build  
**Solución:** `public/storage` está en `.dockerignore`. Recrear con:
```bash
docker compose exec app php artisan storage:link
```

### Build muy lento la primera vez
**Causa:** Compilación de extensiones PHP desde código fuente en Alpine  
**Solución:** Normal en la primera build. Las siguientes usan caché de Docker.
