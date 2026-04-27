#!/bin/bash
# ══════════════════════════════════════════════════════════════════════════════
# Nexo Digital Store — Activar SSL con Let's Encrypt
#
# Ejecutar cuando ya tengas un dominio apuntando al VPS:
#   bash deploy/activate-ssl.sh tudominio.com
# ══════════════════════════════════════════════════════════════════════════════

set -euo pipefail

DOMAIN="${1:-}"
APP_DIR="/var/www/nexokeys"

GREEN='\033[0;32m'; BLUE='\033[0;34m'; RED='\033[0;31m'; NC='\033[0m'
info()  { echo -e "${BLUE}▶ $1${NC}"; }
success(){ echo -e "${GREEN}✓ $1${NC}"; }
error() { echo -e "${RED}✗ $1${NC}"; exit 1; }

[ -n "$DOMAIN" ] || error "Uso: bash deploy/activate-ssl.sh tudominio.com"
[ "$EUID" -eq 0 ] || error "Ejecuta como root"

# Verificar que el dominio resuelve a este servidor
VPS_IP=$(curl -s ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')
DOMAIN_IP=$(dig +short "$DOMAIN" 2>/dev/null | tail -1 || true)

if [ "$DOMAIN_IP" != "$VPS_IP" ]; then
    echo -e "${RED}⚠ ADVERTENCIA: ${DOMAIN} resuelve a ${DOMAIN_IP:-'(sin respuesta)'} pero este servidor es ${VPS_IP}${NC}"
    echo "El DNS aún no apunta al VPS. El certificado SSL fallará."
    read -rp "¿Continuar de todos modos? [s/N]: " ok
    [[ "$ok" =~ ^[sS]$ ]] || exit 1
fi

info "Instalando config Nginx para ${DOMAIN}..."
cp "${APP_DIR}/deploy/nginx.conf" /etc/nginx/sites-available/nexokeys

# Reemplazar placeholder del dominio
sed -i "s/TU_DOMINIO/${DOMAIN}/g" /etc/nginx/sites-available/nexokeys

# Comentar bloque SSL temporalmente (Certbot lo configurará)
# El bloque HTTP-only es suficiente para que Certbot valide
ln -sf /etc/nginx/sites-available/nexokeys /etc/nginx/sites-enabled/nexokeys
nginx -t && systemctl reload nginx
success "Nginx configurado para ${DOMAIN}"

info "Obteniendo certificado SSL para ${DOMAIN} y www.${DOMAIN}..."
certbot --nginx \
    -d "$DOMAIN" \
    -d "www.${DOMAIN}" \
    --non-interactive \
    --agree-tos \
    --email "admin@${DOMAIN}" \
    --redirect
success "SSL activado para ${DOMAIN}"

info "Verificando renovación automática..."
systemctl enable certbot.timer 2>/dev/null || true
certbot renew --dry-run
success "Renovación automática configurada"

echo ""
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ SSL activo — https://${DOMAIN}${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
