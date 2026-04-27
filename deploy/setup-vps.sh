#!/bin/bash
# ══════════════════════════════════════════════════════════════════════════════
# Nexo Digital Store — Instalación inicial del VPS
# Compatible: Ubuntu 24.04 LTS (Noble Numbat)
#
# Ejecutar UNA SOLA VEZ como root:
#   bash setup-vps.sh
#
# Qué hace:
#   1. Actualiza el sistema
#   2. Instala PHP 8.3 (PPA ondrej/php), Nginx, MySQL 8, Redis, Node 20, Composer
#   3. Crea el usuario "nexo" y el directorio /var/www/nexokeys
#   4. Clona el repositorio
#   5. Instala Supervisor para Horizon
#   6. Configura SSH con clave pública (más seguro que contraseña)
#   7. (Opcional) SSL con Certbot — requiere dominio
# ══════════════════════════════════════════════════════════════════════════════

set -euo pipefail

# ── Variables — EDITAR ANTES DE EJECUTAR ─────────────────────────────────────
APP_USER="nexo"
APP_DIR="/var/www/nexokeys"
PHP_VERSION="8.3"
NODE_VERSION="20"
REPO_URL=""          # ← git@github.com:tu-usuario/nexokeys.git
DOMAIN=""            # ← tu dominio cuando lo tengas (déjalo vacío por ahora)
DB_NAME="nexokeys"
DB_USER="nexo_db"
DB_PASS=""           # ← contraseña segura para MySQL (mínimo 16 chars)

# Tu clave pública SSH (cat ~/.ssh/id_ed25519.pub en tu máquina local)
SSH_PUBLIC_KEY=""    # ← pega aquí tu clave pública (opcional pero recomendado)

# Colores
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; RED='\033[0;31m'; NC='\033[0m'
info()    { echo -e "${BLUE}▶ $1${NC}"; }
success() { echo -e "${GREEN}✓ $1${NC}"; }
warn()    { echo -e "${YELLOW}⚠ $1${NC}"; }
error()   { echo -e "${RED}✗ $1${NC}"; exit 1; }

# ── Validaciones previas ──────────────────────────────────────────────────────
[ "$EUID" -eq 0 ] || error "Ejecuta como root: sudo bash setup-vps.sh"
[ -n "$DB_PASS" ]  || error "Completa la variable DB_PASS antes de ejecutar."

if [ -f /etc/os-release ]; then
    . /etc/os-release
    if [[ "$ID" != "ubuntu" ]]; then
        warn "Este script está optimizado para Ubuntu 24. Tu sistema: $PRETTY_NAME"
        read -rp "¿Continuar de todos modos? [s/N]: " ok
        [[ "$ok" =~ ^[sS]$ ]] || exit 1
    fi
fi

# ── 1. Sistema base ───────────────────────────────────────────────────────────
info "Actualizando Ubuntu 24..."
apt-get update -qq
DEBIAN_FRONTEND=noninteractive apt-get upgrade -y -qq
apt-get install -y -qq \
    curl wget git unzip zip gnupg2 \
    ca-certificates lsb-release software-properties-common \
    ufw fail2ban sudo
success "Sistema actualizado"

# ── 2. PHP 8.3 (PPA ondrej/php — estándar en Ubuntu) ─────────────────────────
info "Agregando PPA ondrej/php e instalando PHP ${PHP_VERSION}..."
add-apt-repository -y ppa:ondrej/php
apt-get update -qq
apt-get install -y -qq \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-opcache

# OPcache optimizado para producción
cat > /etc/php/${PHP_VERSION}/fpm/conf.d/99-nexo-opcache.ini << 'EOF'
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
EOF

# PHP.ini — ajustes de producción
sed -i 's/^upload_max_filesize.*/upload_max_filesize = 20M/'   /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i 's/^post_max_size.*/post_max_size = 20M/'               /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i 's/^memory_limit.*/memory_limit = 256M/'               /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i 's/^max_execution_time.*/max_execution_time = 300/'     /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i 's/^expose_php.*/expose_php = Off/'                     /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i 's/^display_errors.*/display_errors = Off/'             /etc/php/${PHP_VERSION}/fpm/php.ini

systemctl enable php${PHP_VERSION}-fpm
systemctl start php${PHP_VERSION}-fpm
success "PHP ${PHP_VERSION} instalado"

# ── 3. Nginx ──────────────────────────────────────────────────────────────────
info "Instalando Nginx..."
apt-get install -y -qq nginx
systemctl enable nginx
systemctl start nginx
success "Nginx instalado"

# ── 4. MySQL 8 ───────────────────────────────────────────────────────────────
# Ubuntu 24.04 ya incluye MySQL 8 en sus repos base
info "Instalando MySQL 8..."
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq mysql-server

# Asegurar que el socket está listo antes de crear la DB
systemctl start mysql
sleep 3

mysql -u root << SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

# Configurar charset global
cat > /etc/mysql/conf.d/nexo.cnf << 'EOF'
[mysqld]
character-set-server  = utf8mb4
collation-server      = utf8mb4_unicode_ci
default-storage-engine = InnoDB
innodb_buffer_pool_size = 256M
max_connections = 200
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

[client]
default-character-set = utf8mb4
EOF

systemctl enable mysql
systemctl restart mysql
success "MySQL 8 instalado (DB: ${DB_NAME}, usuario: ${DB_USER})"

# ── 5. Redis ──────────────────────────────────────────────────────────────────
info "Instalando Redis..."
apt-get install -y -qq redis-server

# Ubuntu 24 usa /etc/redis/redis.conf
sed -i 's/^# maxmemory .*/maxmemory 256mb/'             /etc/redis/redis.conf
sed -i 's/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
# Enlazar solo a localhost
sed -i 's/^bind .*/bind 127.0.0.1 -::1/'                /etc/redis/redis.conf

systemctl enable redis-server
systemctl restart redis-server
success "Redis instalado"

# ── 6. Node.js 20 ────────────────────────────────────────────────────────────
info "Instalando Node.js ${NODE_VERSION}..."
curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -
apt-get install -y -qq nodejs
success "Node.js $(node -v) instalado"

# ── 7. Composer ───────────────────────────────────────────────────────────────
info "Instalando Composer..."
curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer
success "Composer $(composer --version --no-ansi 2>&1 | head -1) instalado"

# ── 8. Supervisor ─────────────────────────────────────────────────────────────
info "Instalando Supervisor..."
apt-get install -y -qq supervisor
systemctl enable supervisor
systemctl start supervisor
success "Supervisor instalado"

# ── 9. Certbot (snap — recomendado en Ubuntu 24) ─────────────────────────────
info "Instalando Certbot via snap..."
snap install --classic certbot
ln -sf /snap/bin/certbot /usr/bin/certbot
success "Certbot instalado (úsalo cuando tengas dominio)"

# ── 10. Usuario de la aplicación ──────────────────────────────────────────────
info "Creando usuario ${APP_USER}..."
if ! id -u "$APP_USER" &>/dev/null; then
    useradd -m -s /bin/bash "$APP_USER"
fi
usermod -aG www-data "$APP_USER"

if [ -n "$SSH_PUBLIC_KEY" ]; then
    mkdir -p /home/${APP_USER}/.ssh
    echo "$SSH_PUBLIC_KEY" >> /home/${APP_USER}/.ssh/authorized_keys
    chown -R ${APP_USER}:${APP_USER} /home/${APP_USER}/.ssh
    chmod 700 /home/${APP_USER}/.ssh
    chmod 600 /home/${APP_USER}/.ssh/authorized_keys
    success "Clave SSH agregada para ${APP_USER}"
fi

# Permisos sudo mínimos para el deploy
cat > /etc/sudoers.d/nexo-deploy << 'SUDOERS'
nexo ALL=(ALL) NOPASSWD: /usr/bin/composer, /usr/bin/npm, /usr/bin/git, /usr/sbin/supervisorctl, /usr/bin/systemctl reload php8.3-fpm
SUDOERS
chmod 440 /etc/sudoers.d/nexo-deploy
success "Usuario ${APP_USER} configurado"

# ── 11. Directorio de la aplicación ──────────────────────────────────────────
info "Configurando directorio ${APP_DIR}..."
mkdir -p "$APP_DIR"

if [ -n "$REPO_URL" ]; then
    if [ ! -d "${APP_DIR}/.git" ]; then
        sudo -u "$APP_USER" git clone "$REPO_URL" "$APP_DIR"
        success "Repositorio clonado"
    else
        warn "Repositorio ya existe, omitiendo clone"
    fi
else
    warn "REPO_URL vacío — clona manualmente después: git clone TU_REPO ${APP_DIR}"
fi

chown -R "${APP_USER}:www-data" "$APP_DIR"
chmod -R 755 "$APP_DIR"
success "Directorio configurado"

# ── 12. Nginx — config temporal o con dominio ─────────────────────────────────
if [ -f "${APP_DIR}/deploy/nginx.conf" ] && [ -n "$DOMAIN" ]; then
    cp "${APP_DIR}/deploy/nginx.conf" /etc/nginx/sites-available/nexokeys
    sed -i "s/TU_DOMINIO/${DOMAIN}/g" /etc/nginx/sites-available/nexokeys
    ln -sf /etc/nginx/sites-available/nexokeys /etc/nginx/sites-enabled/nexokeys
    rm -f /etc/nginx/sites-enabled/default
    nginx -t && systemctl reload nginx
    success "Config Nginx instalada para ${DOMAIN}"
else
    # Config temporal HTTP-only — para probar con la IP del VPS
    cat > /etc/nginx/sites-available/nexokeys << NGINX
server {
    listen 80 default_server;
    root ${APP_DIR}/public;
    index index.php;

    client_max_body_size 20M;

    add_header X-Frame-Options        "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff"    always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;
    }

    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    location ~ /\. { deny all; }
}
NGINX
    ln -sf /etc/nginx/sites-available/nexokeys /etc/nginx/sites-enabled/nexokeys
    rm -f /etc/nginx/sites-enabled/default
    nginx -t && systemctl reload nginx
    warn "Config Nginx temporal (HTTP por IP). Activa SSL cuando tengas dominio: bash deploy/activate-ssl.sh"
fi

# ── 13. Config Supervisor ─────────────────────────────────────────────────────
if [ -f "${APP_DIR}/deploy/supervisor-nexo.conf" ]; then
    cp "${APP_DIR}/deploy/supervisor-nexo.conf" /etc/supervisor/conf.d/nexo-horizon.conf
    supervisorctl reread
    supervisorctl update
    success "Config Supervisor instalada (Horizon)"
fi

# ── 14. Firewall UFW ──────────────────────────────────────────────────────────
info "Configurando firewall UFW..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow http
ufw allow https
ufw --force enable
success "Firewall: SSH + HTTP + HTTPS habilitados"

# ── Fin ───────────────────────────────────────────────────────────────────────
VPS_IP=$(curl -s ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')

echo ""
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ Ubuntu 24 configurado — listo para deploy${NC}"
echo ""
echo -e "  IP del servidor: ${YELLOW}${VPS_IP}${NC}"
echo ""
echo -e "  📋 Próximos pasos:"
echo ""
echo -e "  1. Copia tu .env de producción:"
echo -e "     ${BLUE}scp .env root@${VPS_IP}:${APP_DIR}/.env${NC}"
echo -e "     Edita: APP_ENV=production  APP_DEBUG=false"
echo -e "            DB_HOST=127.0.0.1   DB_PASSWORD=${DB_PASS}"
echo -e "            REDIS_HOST=127.0.0.1"
echo ""
echo -e "  2. Primer deploy:"
echo -e "     ${BLUE}ssh root@${VPS_IP} 'cd ${APP_DIR} && bash deploy.sh'${NC}"
echo ""
echo -e "  3. Prueba la app en: ${BLUE}http://${VPS_IP}${NC}"
echo ""
echo -e "  4. Cuando tengas dominio, activa SSL:"
echo -e "     ${BLUE}bash ${APP_DIR}/deploy/activate-ssl.sh tudominio.com${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
