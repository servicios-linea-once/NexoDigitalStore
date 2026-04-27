# Pagos — PayPal y MercadoPago

## Configuración

```env
# PayPal
PAYPAL_CLIENT_ID=...
PAYPAL_CLIENT_SECRET=...
PAYPAL_MODE=sandbox             # sandbox | live
PAYPAL_SANDBOX_CLIENT_ID=...
PAYPAL_SANDBOX_CLIENT_SECRET=...
PAYPAL_WEBHOOK_ID=...

# MercadoPago (Perú)
MERCADOPAGO_PUBLIC_KEY=TEST-...
MERCADOPAGO_ACCESS_TOKEN=TEST-...
MERCADOPAGO_WEBHOOK_SECRET=...
```

---

## Flujo de Pago General

```
1. Usuario hace checkout
2. Sistema reserva claves (lockForUpdate, 15 min)
3. Usuario elige PayPal o MercadoPago
4. Redirección al gateway externo
5. Usuario paga
6. Gateway llama al webhook
7. Webhook verifica firma
8. Sistema completa la orden:
   - Marcar payment como completed
   - Marcar claves como sold
   - Descontar NT bloqueados si aplica
   - Dar cashback en NT al comprador
   - Notificar al comprador (email + Telegram)
9. Página de confirmación con clave revelada
```

---

## PayPal

### Endpoints

| Ruta | Descripción |
|---|---|
| `POST /api/v1/payments/paypal/create-order` | Crear orden PayPal |
| `POST /api/v1/payments/paypal/capture` | Capturar pago |
| `POST /webhook/paypal` | Webhook de PayPal (sin CSRF) |

### Variables en `config/nexo.php`

```php
'payments' => [
    'paypal' => [
        'mode'          => env('PAYPAL_MODE', 'sandbox'),
        'client_id'     => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_id'    => env('PAYPAL_WEBHOOK_ID'),
    ],
]
```

### Verificación de Webhook

```php
// PayPalWebhookController@handle
// 1. Verificar PAYPAL-TRANSMISSION-SIG header
// 2. Marcar payment como webhook_verified = 1
// 3. Procesar evento PAYMENT.CAPTURE.COMPLETED
```

---

## MercadoPago (Perú)

### Monedas soportadas en Perú

| Moneda | Código | Símbolo |
|---|---|---|
| Sol Peruano | PEN | S/ |
| US Dollar | USD | $ |

### Endpoints

| Ruta | Descripción |
|---|---|
| `POST /api/v1/payments/mercadopago/create-preference` | Crear preferencia MP |
| `POST /webhook/mercadopago` | Webhook de MercadoPago (sin CSRF) |

### Proceso

```
1. Crear Preference en API de MP:
   {
     "items": [...],
     "back_urls": {
       "success": "/checkout/success",
       "failure": "/checkout/failed"
     },
     "auto_return": "approved",
     "notification_url": "/webhook/mercadopago"
   }

2. Redirigir a preference.init_point (URL de pago MP)

3. MP notifica el webhook con:
   ?id={payment_id}&topic=payment

4. Consultar el pago por ID y procesar
```

---

## Pagos con NexoTokens (NT)

Los NT se pueden usar como descuento o pago total.

```
Ejemplo: Orden de $10 USD
Usuario usa 50 NT ($5.00 USD)
Pago restante con PayPal: $5.00 USD

Flujo:
1. Lock 50 NT en wallet
2. Crear orden con mercadopago_amount=$5.00
3. Al confirmar pago:
   - Debitar 50 NT del wallet
   - Procesar $5.00 con PayPal/MP
```

---

## Reembolsos

| Escenario | Acción |
|---|---|
| Clave inválida reportada | Abrir disputa → admin aprueba refund |
| Duplicado de compra | Admin procesa reembolso manual |
| Clave no entregada (timeout) | Sistema devuelve NT + reversa PayPal/MP |

> Los reembolsos se procesan desde el panel de admin. No hay reembolso automático.
