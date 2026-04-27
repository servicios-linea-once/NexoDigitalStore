# Bot de Telegram — Nexo Digital Store

**Bot:** `@key_master_lineaonce_bot`  
**Token:** `TELEGRAM_BOT_TOKEN` en `.env`

---

## Comandos disponibles

| Comando | Descripción |
|---|---|
| `/start` | Bienvenida + menú principal |
| `/start vincular_TOKEN` | Vincula cuenta web automáticamente |
| `/catalogo` | Ver productos activos con stock |
| `/buscar [texto]` | Buscar por nombre o plataforma |
| `/carrito` | Ver carrito del bot |
| `/pedidos` | Ver últimos 5 pedidos (requiere cuenta vinculada) |
| `/saldo` | Ver balance NT (requiere cuenta vinculada) |
| `/vincular` | Instrucciones para vincular cuenta |
| `/desvincular` | Desconectar cuenta de Nexo |
| `/ayuda` | Lista de todos los comandos |

---

## Arquitectura

```
Telegram API
    │
    ▼  POST /webhook/telegram (sin CSRF)
TelegramWebhookController@handle
    ├── handleMessage()  → handleCommand() / handleState()
    └── handleCallback() → showProduct() / addToCart() / ...
    
TelegramService
    ├── sendMessage(chatId, text, keyboard?)
    ├── answerCallbackQuery(callbackId, text?)
    ├── setWebhook(url)
    └── Builders: inlineKeyboard() / replyKeyboard()
```

---

## Flujo de vinculación de cuenta

```
[Web] Usuario en /profile/security
    │
    ▼ Clic "Generar enlace de vinculación"
POST /profile/telegram/token
    ├── Genera token aleatorio (32 chars, TTL 15 min)
    ├── Guarda en users.telegram_link_token
    └── Devuelve: https://t.me/key_master_lineaonce_bot?start=vincular_TOKEN
    │
    ▼ Usuario abre el enlace en Telegram
Bot recibe: /start vincular_TOKEN
    ├── Busca token en users (no expirado)
    ├── Asocia TelegramUser.user_id = User.id
    ├── Marca is_linked = true
    ├── Limpia tokens de la tabla users
    └── Confirma vinculación al usuario en Telegram

[Web] Sección seguridad muestra: ✓ Vinculado @username
```

---

## Configurar en desarrollo (ngrok)

> Telegram requiere HTTPS. Para desarrollo local usa **ngrok**:

```bash
# 1. Instalar ngrok: https://ngrok.com/download
ngrok http 8000

# 2. Copiar la URL HTTPS que te da ngrok:
# Ejemplo: https://abc123.ngrok-free.app

# 3. Registrar el webhook:
docker compose exec app php artisan telegram:set-webhook
# (el APP_URL del .env debe ser la URL de ngrok)

# O directamente:
docker compose exec app php artisan tinker --execute="
  \$tg = new App\Services\TelegramService();
  print_r(\$tg->setWebhook('https://ABC123.ngrok-free.app/webhook/telegram'));
"
```

---

## Configurar en producción

```bash
# Una sola vez al desplegar:
php artisan telegram:set-webhook

# Para eliminar el webhook:
php artisan telegram:set-webhook --delete

# Ver estado del webhook:
docker compose exec app php artisan tinker --execute="
  print_r((new App\Services\TelegramService())->getWebhookInfo());
"
```

---

## Variables de entorno requeridas

```env
TELEGRAM_BOT_TOKEN=8728103624:AAHapASpZ-bsMOSO9eso34XGEeEYSO7jUSM
TELEGRAM_BOT_USERNAME=key_master_lineaonce_bot
```

---

## Tabla `telegram_users`

| Campo | Tipo | Descripción |
|---|---|---|
| `telegram_id` | string | ID único de Telegram |
| `user_id` | FK nullable | Usuario Nexo vinculado |
| `username` | string nullable | @username en Telegram |
| `first_name`, `last_name` | string | Nombre del usuario |
| `is_linked` | boolean | Si tiene cuenta vinculada |
| `link_token` | string nullable | Token temporal de vinculación |
| `link_token_expires_at` | datetime | Expiración del token (15 min) |
| `state` | string nullable | Estado conversacional del bot |
| `cart` | JSON | Carrito del bot |
| `preferred_currency` | string | Moneda preferida |
| `notifications_enabled` | boolean | Notificaciones push |
| `last_interaction_at` | datetime | Última actividad |

---

## Callbacks inline (botones)

| `callback_data` | Acción |
|---|---|
| `cmd_catalogo` | Muestra catálogo |
| `cmd_vincular` | Instrucciones de vinculación |
| `cmd_ayuda` | Muestra ayuda |
| `cart_clear` | Vacía el carrito |
| `product_{id}` | Detalle del producto |
| `add_to_cart_{id}` | Añade al carrito del bot |
