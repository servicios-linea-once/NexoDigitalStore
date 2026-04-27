# 🔐 GitHub Secrets — Configuración para CI/CD

Ve a: **GitHub → tu repo → Settings → Secrets and variables → Actions → New repository secret**

## Secrets requeridos para el deploy automático

| Secret | Descripción | Cómo obtenerlo |
|--------|-------------|----------------|
| `VPS_HOST` | IP pública del VPS | Panel de control de tu proveedor (Contabo, etc.) |
| `VPS_USER` | Usuario SSH (`root`) | El que usas para conectarte |
| `VPS_SSH_KEY` | Clave privada SSH (contenido completo) | Ver instrucciones abajo |
| `VPS_PORT` | Puerto SSH (default: `22`) | Generalmente `22`, cambiarlo si modificaste sshd |

---

## ¿Cómo generar y configurar la clave SSH?

### 1. Generar par de claves (en tu PC local)

```bash
# Genera clave ed25519 (más segura y compacta)
ssh-keygen -t ed25519 -C "github-actions-nexokeys" -f ~/.ssh/nexokeys_deploy
```

Esto crea dos archivos:
- `~/.ssh/nexokeys_deploy` → **PRIVADA** (va en GitHub Secrets)
- `~/.ssh/nexokeys_deploy.pub` → **PÚBLICA** (va en el VPS)

### 2. Agregar clave pública al VPS

```bash
# Copia la clave pública al VPS
ssh-copy-id -i ~/.ssh/nexokeys_deploy.pub root@TU_IP_VPS

# O manualmente:
cat ~/.ssh/nexokeys_deploy.pub | ssh root@TU_IP_VPS "cat >> ~/.ssh/authorized_keys"
```

### 3. Agregar clave privada a GitHub Secrets

```bash
# Ver el contenido de la clave privada
cat ~/.ssh/nexokeys_deploy
```

Copia TODO el contenido (incluyendo `-----BEGIN OPENSSH PRIVATE KEY-----` y `-----END OPENSSH PRIVATE KEY-----`) y pégalo en el Secret `VPS_SSH_KEY`.

### 4. Verificar conexión antes de probar el workflow

```bash
ssh -i ~/.ssh/nexokeys_deploy root@TU_IP_VPS "echo OK"
```

---

## Configuración completa de los Secrets

```
VPS_HOST     → 123.456.789.0        (IP de tu VPS)
VPS_USER     → root
VPS_SSH_KEY  → -----BEGIN OPENSSH PRIVATE KEY-----
               b3BlbnNzaC1rZXktdjEAAAAA...
               -----END OPENSSH PRIVATE KEY-----
VPS_PORT     → 22
```

---

## Pre-requisitos en el VPS antes del primer deploy

El VPS debe tener el repositorio clonado y el `.env` configurado:

```bash
# 1. Clonar el repo (primera vez)
cd /var/www
git clone https://github.com/servicios-linea-once/NexoDigitalStore.git nexokeys

# 2. Copiar y configurar .env
cp /var/www/nexokeys/.env.example /var/www/nexokeys/.env
nano /var/www/nexokeys/.env   # ← editar con variables de producción

# 3. Generar app key
cd /var/www/nexokeys
php artisan key:generate
```

---

## Flujo de trabajo

```
Tu PC (git push main)
       ↓
GitHub Actions (detecta push)
       ↓
CI: tests pasan ✅
       ↓
Deploy: SSH al VPS
  → git pull
  → composer install
  → npm run build
  → php artisan migrate
  → optimizar Laravel
  → reiniciar servicios
       ↓
App actualizada en producción 🚀
```
