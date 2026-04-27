#!/bin/bash
# ══════════════════════════════════════════════════════════════════════════════
# Nexo Digital Store — Deploy al VPS (modo native SSH)
#
# Uso desde tu máquina local:
#   ssh usuario@IP_VPS "cd /var/www/nexokeys && bash deploy.sh"
#
# O directamente en el VPS:
#   cd /var/www/nexokeys && bash deploy.sh
#
# Prerrequisito: haber ejecutado deploy/setup-vps.sh una vez antes.
# ══════════════════════════════════════════════════════════════════════════════

set -euo pipefail

# ── Configuración ─────────────────────────────────────────────────────────────
APP_DIR="${APP_DIR:-/var/www/nexokeys}"
APP_USER="${APP_USER:-nexo}"
PHP_VERSION="8.3"
GIT_BRANCH="main"

# Colores
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; RED='\033[0;31m'; NC='\033[0m'
info()    { echo -e "${BLUE}▶ $1${NC}"; }
success() { echo -e "${GREEN}✓ $1${NC}"; }
warn()    { echo -e "${YELLOW}⚠ $1${NC}"; }
error()   { echo -e "${RED}✗ $1${NC}"; exit 1; }

# ── Validaciones ──────────────────────────────────────────────────────────────
[ -d "$APP_DIR" ] || error "Directorio no encontrado: ${APP_DIR}"
[ -f "${APP_DIR}/.env" ] || error "Falta .env en ${APP_DIR}. Cópialo antes de deployar."

cd "$APP_DIR"

echo ""
echo -e "${BLUE}══════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  🚀 Nexo Deploy — $(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo -e "${BLUE}══════════════════════════════════════════════════════${NC}"
echo ""

# ── 1. Activar modo mantenimiento ─────────────────────────────────────────────
info "Activando modo mantenimiento..."
sudo -u "$APP_USER" php artisan down --retry=60
success "App en mantenimiento"

# Garantizar que la app vuelva online si el script falla
trap 'warn "Error durante deploy. Levantando la app..."; sudo -u "$APP_USER" php artisan up' ERR

# ── 2. Actualizar código ──────────────────────────────────────────────────────
info "Actualizando código desde Git (rama: ${GIT_BRANCH})..."
sudo -u "$APP_USER" git fetch origin "$GIT_BRANCH"
sudo -u "$APP_USER" git reset --hard "origin/${GIT_BRANCH}"
success "Código actualizado"

# ── 3. Dependencias PHP ───────────────────────────────────────────────────────
info "Instalando dependencias PHP..."
sudo -u "$APP_USER" composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --quiet
success "Dependencias PHP instaladas"

# ── 4. Assets frontend ────────────────────────────────────────────────────────
info "Compilando assets frontend..."
sudo -u "$APP_USER" npm ci --silent
sudo -u "$APP_USER" npm run build
success "Assets compilados"

# ── 5. Migraciones ───────────────────────────────────────────────────────────
info "Ejecutando migraciones..."
sudo -u "$APP_USER" php artisan migrate --force
success "Migraciones completadas"

# ── 6. Optimizar Laravel ─────────────────────────────────────────────────────
info "Optimizando Laravel..."
sudo -u "$APP_USER" php artisan optimize:clear
sudo -u "$APP_USER" php artisan config:cache
sudo -u "$APP_USER" php artisan route:cache
sudo -u "$APP_USER" php artisan view:cache
sudo -u "$APP_USER" php artisan event:cache
sudo -u "$APP_USER" php artisan ziggy:generate
success "Laravel optimizado"

# ── 7. Permisos ───────────────────────────────────────────────────────────────
info "Ajustando permisos..."
chown -R "${APP_USER}:www-data" "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
chmod 600 "${APP_DIR}/.env"
success "Permisos ajustados"

# ── 8. Reiniciar servicios ────────────────────────────────────────────────────
info "Reiniciando servicios..."
systemctl reload php${PHP_VERSION}-fpm
sudo -u "$APP_USER" php artisan queue:restart
supervisorctl reread
supervisorctl update
supervisorctl restart nexo-horizon:* 2>/dev/null || warn "Horizon no estaba corriendo, inícialo con: supervisorctl start nexo-horizon:*"
success "Servicios reiniciados"

# ── 9. Quitar modo mantenimiento ─────────────────────────────────────────────
info "Levantando la app..."
sudo -u "$APP_USER" php artisan up

# Limpiar el trap de error ya que todo fue bien
trap - ERR

echo ""
echo -e "${GREEN}══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ Deploy completado — $(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo -e "${GREEN}══════════════════════════════════════════════════════${NC}"
echo ""
