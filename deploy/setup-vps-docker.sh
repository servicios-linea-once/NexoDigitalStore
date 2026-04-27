#!/bin/bash
# ══════════════════════════════════════════════════════════════════════════════
# Nexo Digital Store — Setup inicial VPS Ubuntu 24 con Docker pre-instalado
#
# Ejecutar UNA SOLA VEZ como root:
#   bash deploy/setup-vps-docker.sh
# ══════════════════════════════════════════════════════════════════════════════

set -euo pipefail

# ── Variables — EDITAR ANTES DE EJECUTAR ─────────────────────────────────────
REPO_URL=""          # ← git@github.com:tu-usuario/nexokeys.git
APP_DIR="/var/www/nexokeys"
APP_USER="nexo"
DOMAIN=""            # ← tu dominio cuando lo tengas (déjalo vacío por ahora)

# Tu clave pública SSH (cat ~/.ssh/id_ed25519.pub en tu máquina local)
SSH_PUBLIC_KEY=""    # ← pega aquí para acceso sin contraseña (recomendado)

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; RED='\033[0;31m'; NC='\033[0m'
info()    { echo -e "${BLUE}▶ $1${NC}"; }
success() { echo -e "${GREEN}✓ $1${NC}"; }
warn()    { echo -e "${YELLOW}⚠ $1${NC}"; }
error()   { echo -e "${RED}✗ $1${NC}"; exit 1; }

[ "$EUID" -eq 0 ] || error "Ejecuta como root"
[ -n "$REPO_URL" ] || error "Rellena REPO_URL antes de ejecutar"

# ── 1. Sistema base ───────────────────────────────────────────────────────────
info "Actualizando Ubuntu 24..."
apt-get update -qq && apt-get upgrade -y -qq
apt-get install -y -qq curl wget git ufw fail2ban
success "Sistema actualizado"

# ── 2. Verificar Docker ───────────────────────────────────────────────────────
info "Verificando Docker..."
if ! command -v docker &>/dev/null; then
    warn "Docker no encontrado. Instalando..."
    curl -fsSL https://get.docker.com | bash
    systemctl enable docker
    systemctl start docker
fi

# Verificar Docker Compose (plugin v2)
if ! docker compose version &>/dev/null; then
    warn "Docker Compose plugin no encontrado. Instalando..."
    apt-get install -y -qq docker-compose-plugin
fi

success "Docker $(docker --version) — Compose $(docker compose version --short)"

# ── 3. Certbot en el host (para SSL cuando tengas dominio) ───────────────────
info "Instalando Certbot..."
apt-get install -y -qq certbot python3-certbot-nginx
success "Certbot instalado"

# ── 4. Nginx en el host SOLO como proxy SSL → contenedor ─────────────────────
# (Opcional: para manejar SSL en el host y pasar tráfico al contenedor Docker)
# Déjalo comentado si prefieres manejar SSL dentro de Docker
# apt-get install -y -qq nginx

# ── 5. Usuario de la aplicación ──────────────────────────────────────────────
info "Configurando usuario ${APP_USER}..."
if ! id -u "$APP_USER" &>/dev/null; then
    useradd -m -s /bin/bash "$APP_USER"
fi
usermod -aG docker "$APP_USER"  # Permite usar Docker sin sudo

if [ -n "$SSH_PUBLIC_KEY" ]; then
    mkdir -p /home/${APP_USER}/.ssh
    echo "$SSH_PUBLIC_KEY" >> /home/${APP_USER}/.ssh/authorized_keys
    chown -R ${APP_USER}:${APP_USER} /home/${APP_USER}/.ssh
    chmod 700 /home/${APP_USER}/.ssh
    chmod 600 /home/${APP_USER}/.ssh/authorized_keys
    success "Clave SSH agregada para ${APP_USER}"
fi
success "Usuario ${APP_USER} configurado (grupo docker)"

# ── 6. Clonar repositorio ─────────────────────────────────────────────────────
info "Clonando repositorio en ${APP_DIR}..."
mkdir -p "$APP_DIR"

if [ ! -d "${APP_DIR}/.git" ]; then
    git clone "$REPO_URL" "$APP_DIR"
    success "Repositorio clonado"
else
    warn "Repositorio ya existe, omitiendo clone"
fi

chown -R "${APP_USER}:${APP_USER}" "$APP_DIR"

# ── 7. Firewall ───────────────────────────────────────────────────────────────
info "Configurando UFW..."
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
echo -e "${GREEN}  ✅ VPS Ubuntu 24 configurado — listo para Docker${NC}"
echo ""
echo -e "  IP del servidor: ${YELLOW}${VPS_IP}${NC}"
echo ""
echo -e "  📋 Próximos pasos:"
echo ""
echo -e "  1. Copia tu .env de producción al VPS:"
echo -e "     ${BLUE}scp .env ${APP_USER}@${VPS_IP}:${APP_DIR}/.env${NC}"
echo ""
echo -e "  2. Edita el .env con las variables de producción:"
echo -e "     ${BLUE}ssh ${APP_USER}@${VPS_IP} 'nano ${APP_DIR}/.env'${NC}"
echo -e "     Cambia: APP_ENV=production, APP_DEBUG=false"
echo -e "     Agrega: DB_ROOT_PASSWORD=contraseña-root-mysql"
echo ""
echo -e "  3. Primer deploy:"
echo -e "     ${BLUE}ssh ${APP_USER}@${VPS_IP} 'cd ${APP_DIR} && bash deploy-docker.sh'${NC}"
echo ""
echo -e "  4. Prueba en: ${BLUE}http://${VPS_IP}${NC}"
echo ""
echo -e "  5. Cuando tengas dominio, activa SSL:"
echo -e "     ${BLUE}bash ${APP_DIR}/deploy/activate-ssl.sh tudominio.com${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
