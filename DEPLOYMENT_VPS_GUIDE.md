# 🚀 Guía Completa de Deployment: Nexo Digital Store en VPS

## Tabla de Contenidos
1. [Pre-Requisitos](#pre-requisitos)
2. [Preparación del VPS](#preparación-del-vps)
3. [Configuración de `.env` para Producción](#configuración-de-env-para-producción)
4. [Instalación y Setup](#instalación-y-setup)
5. [Variables de Entorno Críticas](#variables-de-entorno-críticas)
6. [Checklist de Seguridad](#checklist-de-seguridad)
7. [Post-Deployment](#post-deployment)

---

## Pre-Requisitos

### Software Requerido
- **PHP 8.2+** (con extensiones: mysql, redis, curl, gd, bcmath, xml, json)
- **MySQL 8.0+** o MariaDB 10.6+
- **Redis 6.0+** (para caché, sesiones y colas)
- **Nginx** o **Apache** (recomendado: Nginx)
- **Node.js 18+** y **npm/yarn** (para compilar assets)
- **Composer** 2.0+
- **Git**

### Ejemplo de Instalación (Ubuntu 22.04)
```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar PHP con extensiones
sudo apt install -y php8.2-fpm php8.2-mysql php8.2-redis php8.2-curl \
  php8.2-gd php8.2-bcmath php8.2-xml php8.2-mbstring php8.2-json

# Instalar MySQL
sudo apt install -y mysql-server

# Instalar Redis
sudo apt install -y redis-server

# Instalar Nginx
sudo apt install -y nginx

# Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## Preparación del VPS

### 1. Crear Usuario para la Aplicación
```bash
# Crear usuario sin shell
sudo useradd -s /bin/false -d /var/www/nexo -m nexo-app

# O si necesitas shell para deployment:
sudo useradd -s /bin/bash -d /var/www/nexo -m nexo-app
```

### 2. Crear Estructura de Directorios
```bash
sudo mkdir -p /var/www/nexo
sudo chown nexo-app:nexo-app /var/www/nexo
sudo chmod 755 /var/www/nexo

# Directorios de almacenamiento
sudo mkdir -p /var/www/nexo/storage/logs
sudo mkdir -p /var/www/nexo/storage/app
sudo chown -R nexo-app:www-data /var/www/nexo/storage
sudo chmod -R 775 /var/www/nexo/storage
```

### 3. Crear Base de Datos MySQL
```bash
sudo mysql -u root -p << EOF
CREATE DATABASE nexo_digital_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nexo'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON nexo_digital_store.* TO 'nexo'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF
```

### 4. Configurar Redis
```bash
# Editar configuración de Redis
sudo nano /etc/redis/redis.conf

# Cambios recomendados:
# - requirepass your_strong_redis_password  (descomentar y cambiar)
# - maxmemory 256mb                         (ajustar según RAM disponible)
# - maxmemory-policy allkeys-lru            (política de evicción)

# Reiniciar Redis
sudo systemctl restart redis-server
```

---

## Configuración de `.env` para Producción

### 1. Copiar y Configurar `.env`
```bash
# En el VPS, dentro del directorio de la app
cd /var/www/nexo
cp .env.example .env

# Editar .env con variables de producción
nano .env
```

### 2. Variables Críticas a Configurar

#### Application
```env
APP_NAME="Nexo Digital Store"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com           # ✅ CAMBIAR A TU DOMINIO
BCRYPT_ROUNDS=14

LOG_LEVEL=warning                         # warning, error para prod
LOG_CHANNEL=stack
```

#### Database
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nexo_digital_store
DB_USERNAME=nexo
DB_PASSWORD=tu_password_segura_16_chars  # ✅ CAMBIAR
```

#### Redis
```env
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=tu_redis_password          # ✅ Si configuraste requirepass
REDIS_CLIENT=predis
```

#### Sessions & Cache
```env
SESSION_DRIVER=redis
SESSION_ENCRYPT=true                      # ✅ IMPORTANTE: true en prod
CACHE_STORE=redis
CACHE_PREFIX=nexo_
```

#### Mail (CRÍTICO)
```env
MAIL_MAILER=smtp                          # smtp, mailgun, sendgrid, ses
MAIL_HOST=smtp.tu-proveedor.com           # ✅ CAMBIAR
MAIL_PORT=587
MAIL_USERNAME=tu_email@tu_dominio.com     # ✅ CAMBIAR
MAIL_PASSWORD=tu_smtp_password            # ✅ CAMBIAR
MAIL_FROM_ADDRESS=noreply@tu_dominio.com
MAIL_FROM_NAME="Nexo Digital Store"
```

**Opciones de Mail Recomendadas:**
- **Mailgun** (gratuito hasta 100 emails/día)
- **SendGrid** (gratuito: 100 emails/día)
- **AWS SES** (muy barato para volumen alto)
- **Mailtrap** (para desarrollo/testing)

#### Integración de Pagos
```env
# PayPal
PAYPAL_MODE=live                          # sandbox o live
PAYPAL_LIVE_CLIENT_ID=tu_paypal_live_id   # ✅ CAMBIAR
PAYPAL_LIVE_CLIENT_SECRET=tu_secret        # ✅ CAMBIAR
PAYPAL_WEBHOOK_ID=tu_webhook_id            # ✅ CAMBIAR

# MercadoPago (Perú)
MERCADOPAGO_MODE=production                # sandbox o production
MERCADOPAGO_LIVE_PUBLIC_KEY=tu_public_key  # ✅ CAMBIAR
MERCADOPAGO_LIVE_ACCESS_TOKEN=tu_token     # ✅ CAMBIAR
MERCADOPAGO_WEBHOOK_SECRET=tu_secret       # ✅ CAMBIAR
```

#### Servicios Externos
```env
# Cloudinary
CLOUDINARY_CLOUD_NAME=tu_cloud_name        # ✅ CAMBIAR
CLOUDINARY_API_KEY=tu_api_key               # ✅ CAMBIAR
CLOUDINARY_API_SECRET=tu_api_secret         # ✅ CAMBIAR
CLOUDINARY_FOLDER=nexo-digital-store

# Google OAuth (opcional)
GOOGLE_CLIENT_ID=tu_google_client_id        # ✅ SI ACTIVAS GOOGLE LOGIN
GOOGLE_CLIENT_SECRET=tu_google_secret       # ✅ SI ACTIVAS GOOGLE LOGIN

# Telegram Bot (opcional)
TELEGRAM_BOT_TOKEN=tu_bot_token             # ✅ SI ACTIVAS TELEGRAM
TELEGRAM_BOT_USERNAME=tu_bot_username
TELEGRAM_WEBHOOK_URL=https://tu-dominio.com/webhook/telegram
```

#### Seguridad (opcional pero recomendado)
```env
# Sentry para error tracking
SENTRY_LARAVEL_DSN=https://...@sentry.io/project

# Trusted proxies (si está detrás de load balancer)
TRUSTED_PROXIES=*
```

---

## Instalación y Setup

### 1. Clonar Repositorio
```bash
cd /var/www/nexo
git clone https://tu-repo.git .
# o si ya existe:
git pull origin main
```

### 2. Instalar Dependencias PHP
```bash
composer install --optimize-autoloader --no-dev
```

### 3. Generar App Key
```bash
php artisan key:generate
```

### 4. Compilar Assets (Frontend)
```bash
npm install
npm run build  # Para producción (minificado)
```

### 5. Ejecutar Migraciones
```bash
php artisan migrate --force  # --force porque está en producción
```

### 6. Seed de Datos (si necesario)
```bash
php artisan db:seed --class=ProductSeeder
```

### 7. Limpiar Caché
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. Cambiar Permisos
```bash
sudo chown -R nexo-app:www-data /var/www/nexo
sudo chmod -R 755 /var/www/nexo
sudo chmod -R 775 /var/www/nexo/storage
sudo chmod -R 775 /var/www/nexo/bootstrap/cache
```

---

## Variables de Entorno Críticas

### Tabla de Referencia Rápida

| Variable | Descripción | Ejemplo | Crítica |
|----------|-------------|---------|---------|
| `APP_ENV` | environment | production | ✅ |
| `APP_DEBUG` | debug mode | false | ✅ |
| `APP_URL` | dominio público | https://example.com | ✅ |
| `DB_PASSWORD` | contraseña DB | (16+ chars) | ✅ |
| `REDIS_PASSWORD` | contraseña Redis | (si configurado) | ⚠️ |
| `MAIL_MAILER` | proveedor email | smtp | ✅ |
| `MAIL_HOST` | servidor SMTP | smtp.sendgrid.net | ✅ |
| `CLOUDINARY_API_SECRET` | Cloudinary secret | (de Cloudinary) | ✅ |
| `PAYPAL_WEBHOOK_ID` | webhook PayPal | (de PayPal) | ✅ |
| `TELEGRAM_BOT_TOKEN` | token bot | (de BotFather) | ⚠️ |

---

## Checklist de Seguridad

- [ ] **HTTPS/SSL**
  ```bash
  # Instalar Let's Encrypt
  sudo apt install -y certbot python3-certbot-nginx
  sudo certbot certonly --nginx -d tu-dominio.com
  ```

- [ ] **Firewall**
  ```bash
  sudo ufw enable
  sudo ufw allow 22/tcp
  sudo ufw allow 80/tcp
  sudo ufw allow 443/tcp
  ```

- [ ] **Contraseñas Fuertes**
  - DB: 16+ caracteres, mix de letras/números/símbolos
  - Redis: si está en red, usar password fuerte
  - App Key: regenerado con `php artisan key:generate`

- [ ] **Permisos de Archivos**
  ```bash
  sudo chmod 600 /var/www/nexo/.env
  sudo chown nexo-app:nexo-app /var/www/nexo/.env
  ```

- [ ] **Deshabilitar Funciones Peligrosas PHP**
  ```bash
  sudo nano /etc/php/8.2/fpm/php.ini
  
  # Buscar y cambiar:
  disable_functions = exec,passthru,shell_exec,system,proc_open,popen
  ```

- [ ] **Configurar Headers de Seguridad** (en Nginx/config)
  ```
  add_header X-Frame-Options "SAMEORIGIN" always;
  add_header X-Content-Type-Options "nosniff" always;
  add_header X-XSS-Protection "1; mode=block" always;
  add_header Referrer-Policy "strict-origin-when-cross-origin" always;
  ```

- [ ] **Limitar Tamaño de Upload**
  ```bash
  nano /etc/nginx/nginx.conf
  
  # Buscar http { } y agregar:
  client_max_body_size 50M;  # Ajustar según necesidad
  ```

---

## Post-Deployment

### 1. Configurar Supervisor para Colas (Jobs)
```bash
sudo nano /etc/supervisor/conf.d/nexo-worker.conf
```

```ini
[program:nexo-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/nexo/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/nexo/storage/logs/worker.log
user=nexo-app
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start nexo-worker:*
```

### 2. Configurar Cron para Tareas Programadas
```bash
sudo crontab -u nexo-app -e
```

```cron
* * * * * php /var/www/nexo/artisan schedule:run >> /dev/null 2>&1
```

### 3. Configurar Nginx
```bash
sudo nano /etc/nginx/sites-available/nexo
```

```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name tu-dominio.com;

    ssl_certificate /etc/letsencrypt/live/tu-dominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tu-dominio.com/privkey.pem;

    root /var/www/nexo/public;
    index index.php index.html;

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name tu-dominio.com;
    return 301 https://$server_name$request_uri;
}
```

```bash
sudo ln -s /etc/nginx/sites-available/nexo /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 4. Monitoreo y Logs
```bash
# Ver logs de aplicación
tail -f /var/www/nexo/storage/logs/laravel.log

# Ver logs de nginx
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# Ver logs de PHP-FPM
tail -f /var/log/php8.2-fpm.log
```

### 5. Backup Automático
```bash
# Script de backup
sudo nano /usr/local/bin/backup-nexo.sh
```

```bash
#!/bin/bash

BACKUP_DIR="/backups/nexo"
DATE=$(date +%Y%m%d_%H%M%S)

# Backup base de datos
mysqldump -u nexo -p'password' nexo_digital_store | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Backup de datos de usuario
tar -czf "$BACKUP_DIR/storage_$DATE.tar.gz" /var/www/nexo/storage

# Mantener solo últimos 7 días
find "$BACKUP_DIR" -name "*.gz" -mtime +7 -delete

echo "Backup completado: $DATE"
```

```bash
sudo chmod +x /usr/local/bin/backup-nexo.sh
# Agregar a crontab
sudo crontab -e
# 2 2 * * * /usr/local/bin/backup-nexo.sh  (diariamente a las 2:02 AM)
```

---

## 🆘 Troubleshooting

### Error: "No application encryption key has been specified"
```bash
php artisan key:generate
```

### Error: "SQLSTATE[HY000] [2002] Can't connect to MySQL server"
```bash
# Verificar MySQL está corriendo
sudo systemctl status mysql
sudo systemctl restart mysql

# Verificar credenciales en .env
php artisan tinker
DB::connection()->getPdo();  # Si no error, conexión OK
```

### Error: "Connection refused" (Redis)
```bash
# Verificar Redis
sudo systemctl status redis-server
redis-cli ping  # Debería responder PONG

# Si falta contraseña
redis-cli -a tu_redis_password ping
```

### Permisos de Storage
```bash
sudo chown -R www-data:www-data /var/www/nexo/storage
sudo chmod -R 775 /var/www/nexo/storage
```

---

## 📞 Contacto y Soporte

- **Documentación Laravel**: https://laravel.com/docs/11
- **Configuración Nginx**: https://nginx.org/
- **Guía MySQL**: https://dev.mysql.com/doc/
- **Redis**: https://redis.io/documentation

---

**Última actualización:** Abril 2026  
**Versión de Laravel:** 11  
**Stack:** Laravel 11 + Vue 3 + Inertia + PrimeVue
