# 🔐 Guía de Variables de Entorno Críticas para Nexo Digital Store

## Tabla de Contenidos
1. [Variables de Seguridad](#variables-de-seguridad)
2. [Integración de Pagos](#integración-de-pagos)
3. [Servicios de Correo](#servicios-de-correo)
4. [Almacenamiento de Archivos](#almacenamiento-de-archivos)
5. [Servicios Externos](#servicios-externos)
6. [Verificación Post-Deployment](#verificación-post-deployment)

---

## Variables de Seguridad

### APP_KEY
- **Descripción:** Clave de encriptación de la aplicación
- **Generar:** `php artisan key:generate`
- **Importancia:** ✅ CRÍTICA - No puede estar vacía
- **Rotación:** Una vez en el ambiente, cambiarla puede invalidar sesiones existentes
```bash
# Generar una nueva key
php artisan key:generate

# Ver la key generada
grep APP_KEY .env
```

### APP_ENV & APP_DEBUG
```env
APP_ENV=production          # ✅ DEBE SER: production
APP_DEBUG=false             # ✅ DEBE SER: false
```
- En producción, `DEBUG=true` expone información sensible en errores
- `APP_ENV` determina qué configuraciones se cargan

### BCRYPT_ROUNDS
```env
BCRYPT_ROUNDS=14            # Recomendado para producción (default: 12)
```
- Controla la fuerza del hashing de contraseñas
- Aumentar hace más lento pero más seguro
- Valores recomendados: 12 (dev) → 14-15 (prod)

### SESSION_ENCRYPT
```env
SESSION_ENCRYPT=true        # ✅ DEBE SER: true en producción
```
- Encripta los datos de sesión
- Previene manipulación de datos de sesión

---

## Integración de Pagos

### PayPal

#### Obtener Credenciales
1. Ir a: https://developer.paypal.com/dashboard/
2. Crear una aplicación de prueba (sandbox)
3. Copiar Client ID y Secret

#### Configuración para Sandbox
```env
PAYPAL_MODE=sandbox
PAYPAL_SANDBOX_CLIENT_ID=AXxxx...                    # ✅ Required
PAYPAL_SANDBOX_CLIENT_SECRET=EFxx...                 # ✅ Required
PAYPAL_WEBHOOK_ID=0CV76034D7034494W                  # ✅ Required
```

#### Cambiar a Producción (Live)
1. En PayPal Dashboard, obtener credenciales LIVE
2. Cambiar el archivo `config/paypal.php` o `.env`:
```env
PAYPAL_MODE=live
PAYPAL_LIVE_CLIENT_ID=AXxxx...                       # ✅ Required
PAYPAL_LIVE_CLIENT_SECRET=EFxx...                    # ✅ Required
PAYPAL_WEBHOOK_ID=1XX...                             # Create webhook in dashboard
```

#### Configurar Webhook en PayPal
1. Dashboard → Apps & Credentials → Webhooks (Sandbox/Live)
2. URL: `https://tu-dominio.com/webhook/paypal`
3. Eventos a escuchar:
   - `CHECKOUT.ORDER.COMPLETED`
   - `CHECKOUT.ORDER.APPROVED`
4. Copiar **Webhook ID** al `.env`

#### Verificar Conexión
```bash
php artisan tinker
>>> $client = new PayPalClient();
>>> $response = $client->getAccessToken();
>>> echo $response['access_token'];  // Si retorna un token, está OK
```

---

### MercadoPago (Perú)

#### Obtener Credenciales
1. Ir a: https://www.mercadopago.com.pe/developers
2. Ir a "Mis aplicaciones"
3. Crear aplicación
4. Obtener **Public Key** y **Access Token**

#### Configuración para Sandbox
```env
MERCADOPAGO_MODE=sandbox
MERCADOPAGO_SANDBOX_PUBLIC_KEY=TEST-395841d5-cfa3...  # ✅ Required
MERCADOPAGO_SANDBOX_ACCESS_TOKEN=TEST-4453631380...   # ✅ Required
MERCADOPAGO_WEBHOOK_SECRET=91e2b2ce26b715658c05bb... # ✅ Required
```

#### Cambiar a Producción
1. Obtener credenciales LIVE de dashboard
2. Cambiar `.env`:
```env
MERCADOPAGO_MODE=production
MERCADOPAGO_LIVE_PUBLIC_KEY=APP_USR-xxxxx...          # ✅ Required
MERCADOPAGO_LIVE_ACCESS_TOKEN=APP_USR_xxxxx...        # ✅ Required
```

#### Configurar Webhook en MercadoPago
1. Dashboard → Configuración → Notificaciones
2. Agregar webhook:
   - URL: `https://tu-dominio.com/webhook/mercadopago`
   - Eventos: `payment.updated`, `payment.created`
3. Copiar **Secret Token** al `.env`

#### Verificar Conexión
```bash
php artisan tinker
>>> $mp = new MercadoPago\Client(['access_token' => config('mercadopago.access_token')]);
>>> echo "Conectado a MercadoPago";
```

---

## Servicios de Correo

### Opción 1: SendGrid (Recomendado)
#### Obtener API Key
1. Crear cuenta: https://sendgrid.com/
2. Ir a Settings → API Keys
3. Crear nueva key (con permisos de mail)

#### Configuración
```env
MAIL_MAILER=sendgrid
MAIL_FROM_ADDRESS=noreply@tu-dominio.com
MAIL_FROM_NAME="Nexo Digital Store"
SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxx...
```

#### Verificar
```bash
php artisan tinker
>>> Mail::raw('Test email', function($m) { $m->to('test@example.com')->subject('Test'); });
```

---

### Opción 2: AWS SES
#### Obtener Credenciales
1. AWS Console → SES → SMTP Settings
2. Obtener credenciales SMTP

#### Configuración
```env
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=AKIAIOSFODNN7EXAMPLE
MAIL_PASSWORD=BIR...
MAIL_FROM_ADDRESS=noreply@tu-dominio.com
```

#### Notas Importantes
- AWS SES requiere verificación de dominios/emails
- Salida: 200 emails/segundo
- Costo: $0.10 por 1000 emails

---

### Opción 3: Mailgun
#### Obtener Credenciales
1. Crear cuenta: https://mailgun.com/
2. Ir a Sending → Domain Settings
3. Copiar credenciales SMTP

#### Configuración
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.tu-dominio.com
MAILGUN_SECRET=key-xxxxxxxxxxxxxxx
MAIL_FROM_ADDRESS=noreply@tu-dominio.com
```

---

### Opción 4: Mailtrap (Testing)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=xxxxx
MAIL_PASSWORD=xxxxx
MAIL_FROM_ADDRESS=test@example.com
```

---

## Almacenamiento de Archivos

### Cloudinary (Recomendado para Imágenes)

#### Obtener Credenciales
1. Crear cuenta: https://cloudinary.com/
2. Ir a Dashboard
3. Copiar: Cloud Name, API Key, API Secret

#### Configuración
```env
CLOUDINARY_CLOUD_NAME=dlekpa6gn                     # ✅ Required
CLOUDINARY_API_KEY=663352452411449                  # ✅ Required
CLOUDINARY_API_SECRET=D2UWuNi0yl5bIJLLhMMTLxfo318  # ✅ Required (secreto!)
CLOUDINARY_FOLDER=nexo-digital-store
FILESYSTEM_DISK=cloudinary                          # Cambiar de 'local'
```

#### Usar en el Código
```php
// Subir imagen
$url = Storage::disk('cloudinary')->put('products', $file);

// URL pública
echo $url;
```

#### Cambiar Método de Upload
- **Subida al servidor luego a Cloudinary:** Lento pero seguro
- **Upload directo a Cloudinary:** Rápido, usa API
- **Signed upload:** Más seguro, requiere firma

---

### AWS S3 (Alternativa)
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=nexo-uploads
AWS_USE_PATH_STYLE_ENDPOINT=false
```

---

## Servicios Externos

### Cloudinary
✅ Arriba en "Almacenamiento"

### Google OAuth (Social Login)
#### Obtener Credenciales
1. https://console.cloud.google.com/
2. Crear proyecto → OAuth 2.0 Client IDs
3. Tipo: Web application
4. URIs autorizados:
   - `https://tu-dominio.com`
   - `https://tu-dominio.com/auth/google/callback`

#### Configuración
```env
GOOGLE_CLIENT_ID=xxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxx
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

---

### Steam OAuth (Social Login)
#### Obtener Credenciales
1. https://steamcommunity.com/dev/apikey
2. Aceptar términos → Obtener clave API

#### Configuración
```env
STEAM_CLIENT_ID=0                                   # Steam no usa client ID
STEAM_CLIENT_SECRET=TU_STEAM_API_KEY                # ✅ Requerida
STEAM_REDIRECT_URI="${APP_URL}/auth/steam/callback"
```

---

### Telegram Bot
#### Crear Bot
1. Hablar con [@BotFather](https://t.me/BotFather) en Telegram
2. Comando: `/newbot`
3. Seguir instrucciones
4. Copiar token

#### Configuración
```env
TELEGRAM_BOT_TOKEN=8728103624:AAHapASpZ-bsMOSO9eso34XGEeEYSO7jUSM
TELEGRAM_BOT_USERNAME=nexo_bot
TELEGRAM_WEBHOOK_URL=https://tu-dominio.com/webhook/telegram
```

#### Registrar Webhook
```bash
# En la línea de comandos
curl "https://api.telegram.org/bot$TOKEN/setWebhook?url=https://tu-dominio.com/webhook/telegram"

# Verificar webhook
curl "https://api.telegram.org/bot$TOKEN/getWebhookInfo"
```

---

## Verificación Post-Deployment

### Checklist de Validación

```bash
#!/bin/bash

APP_DIR="/var/www/nexo"

echo "🔍 Verificando configuración..."

cd "$APP_DIR"

# 1. Verificar .env existe y es legible
if [ -f .env ]; then
    echo "✓ .env existe"
else
    echo "✗ .env NO existe"
fi

# 2. Verificar variables críticas
CRITICAL_VARS=("APP_KEY" "DB_PASSWORD" "REDIS_HOST" "MAIL_MAILER" "CLOUDINARY_API_KEY" "PAYPAL_MODE")

for var in "${CRITICAL_VARS[@]}"; do
    if grep -q "$var=" .env; then
        echo "✓ $var está configurado"
    else
        echo "✗ $var NO está configurado"
    fi
done

# 3. Verificar conexión a BD
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database OK';"

# 4. Verificar Redis
php artisan tinker --execute="Cache::store('redis')->get('test'); echo 'Redis OK';"

# 5. Verificar Mail
php artisan tinker --execute="Mail::mailable()? 'Mail OK' : 'Mail FAIL';"

echo ""
echo "✅ Verificación completada"
```

### Pruebas Manuales

#### Envío de Email
```bash
php artisan tinker
>>> Mail::raw('Test email', function($m) { $m->to('tu-email@ejemplo.com'); });
```

#### Prueba de Pago (PayPal Sandbox)
1. Ir a: https://www.sandbox.paypal.com/
2. Login con credenciales de test
3. Procesar pago de prueba en tu sitio

#### Prueba de Pago (MercadoPago Sandbox)
1. Usar tarjeta de test: `4111 1111 1111 1111`
2. Cualquier fecha futura y CVV
3. Verificar webhook en dashboard de MercadoPago

---

## 🆘 Troubleshooting

### "MAIL_MAILER es inválido"
```bash
# Verificar que el mailer configurado esté en config/mail.php
grep "'$MAIL_MAILER'" config/mail.php
```

### "No puedo conectar a Cloudinary"
```bash
# Verificar credenciales
php artisan tinker
>>> config('cloudinary.api_secret')  // Debería retornar el secret
```

### "PayPal Webhook no funciona"
1. Verificar URL del webhook es pública y HTTPS
2. Verificar webhook ID en `.env`
3. Revisar logs de webhook en PayPal Dashboard

### "Redis rechaza conexión"
```bash
# Verificar Redis está corriendo
redis-cli ping

# Si tiene password:
redis-cli -a $REDIS_PASSWORD ping
```

---

## 📋 Template Rápido para .env Producción

```env
# ── CRÍTICOS ───────────────────────────────────────────
APP_KEY=base64:...                          # php artisan key:generate
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_PASSWORD=tu_password_super_seguro_16_chars
REDIS_PASSWORD=otro_password_super_seguro

# ── PAGOS ──────────────────────────────────────────────
PAYPAL_MODE=live
PAYPAL_LIVE_CLIENT_ID=...
PAYPAL_LIVE_CLIENT_SECRET=...
PAYPAL_WEBHOOK_ID=...

MERCADOPAGO_MODE=production
MERCADOPAGO_LIVE_PUBLIC_KEY=...
MERCADOPAGO_LIVE_ACCESS_TOKEN=...
MERCADOPAGO_WEBHOOK_SECRET=...

# ── EMAIL ──────────────────────────────────────────────
MAIL_MAILER=sendgrid
SENDGRID_API_KEY=SG....

# ── ALMACENAMIENTO ────────────────────────────────────
CLOUDINARY_CLOUD_NAME=...
CLOUDINARY_API_KEY=...
CLOUDINARY_API_SECRET=...

# ── TELEGRAM ──────────────────────────────────────────
TELEGRAM_BOT_TOKEN=...
TELEGRAM_WEBHOOK_URL=https://tu-dominio.com/webhook/telegram
```

---

**Última actualización:** Abril 2026  
**Versión:** 1.0
