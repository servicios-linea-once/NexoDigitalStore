# NexoTokens (NT) — Economía Interna

## Conceptos Base

| Parámetro | Valor |
|---|---|
| Nombre | NexoToken |
| Símbolo | NT |
| Tasa base | 1 NT = $0.10 USD |
| Cashback por defecto | 2% – 5% según el producto |
| Mínimo de canje | 10 NT ($1.00 USD) |
| Expiración | Sin expiración |

---

## Wallet

Cada usuario tiene exactamente **1 wallet** creada automáticamente al registrarse.

```php
// Creación automática en AuthController@register
Wallet::create([
    'user_id'  => $user->id,
    'balance'  => 0,
    'currency' => 'NT',
]);

// Balance disponible
$available = $wallet->balance - $wallet->locked_balance;
```

### Campos del Wallet

| Campo | Descripción |
|---|---|
| `balance` | Balance total en NT |
| `locked_balance` | NT reservados para órdenes en proceso |
| `available_balance` | `balance - locked_balance` (accessor) |

---

## Tipos de Transacciones

| Tipo | Descripción |
|---|---|
| `credit` | Ingreso de NT (compra de tokens) |
| `debit` | Gasto de NT (pago de orden) |
| `lock` | Bloqueo temporal durante checkout |
| `unlock` | Liberación de NT bloqueados (orden cancelada) |
| `cashback` | NT ganados por una compra |
| `refund` | NT devueltos por reembolso |
| `purchase` | NT usados para pagar una orden |

---

## Flujo de Cashback

```
1. Comprador completa un pago de $10 USD
2. El producto tiene cashback_percent = 5%
3. cashback_nt = (10 * 0.05) / 0.10 = 5 NT
4. Se crea WalletTransaction:
   - type = 'cashback'
   - amount = 5
   - balance_after = wallet.balance + 5
   - reference_type = 'OrderItem'
   - reference_id = $orderItem->id
5. Wallet::increment('balance', 5)
```

---

## Flujo de Pago con NT

```
Checkout con NT:
1. Verificar available_balance >= nt_a_usar
2. DB::transaction:
   a. WalletTransaction(type='lock', amount=nt_a_usar)
   b. Wallet::increment('locked_balance', nt_a_usar)
3. Al completar pago:
   a. WalletTransaction(type='purchase', amount=nt_a_usar)
   b. Wallet::decrement('balance', nt_a_usar)
   c. Wallet::decrement('locked_balance', nt_a_usar)
4. Si falla el pago:
   a. WalletTransaction(type='unlock', amount=nt_a_usar)
   b. Wallet::decrement('locked_balance', nt_a_usar)
```

---

## Tasa de Cambio y Conversiones

```php
// En Currency model
public static function convert(float $amount, string $from, string $to): float
{
    if ($from === $to) return $amount;

    $fromRate = static::where('code', $from)->value('rate_to_usd');
    $toRate   = static::where('code', $to)->value('rate_to_usd');

    // Primero a USD, luego a la moneda destino
    $usd = $amount / $fromRate;
    return round($usd * $toRate, 4);
}

// Ejemplos:
Currency::convert(100, 'NT', 'USD');  // 100 NT → $10.00 USD
Currency::convert(10, 'USD', 'PEN');  // $10.00 → S/37.50
Currency::convert(50, 'NT', 'PEN');   // 50 NT → S/18.75
```

---

## Planes de Suscripción

Los planes no tienen renovación automática. El usuario debe renovar manualmente.

### Estructura de Planes (ejemplo)

| Plan | Precio USD | Precio NT | Características |
|---|---|---|---|
| Starter | $0 | 0 NT | Compras básicas, historial |
| Pro | $9.99/mes | 100 NT/mes | + API access, bot Telegram |
| Business | $29.99/mes | 300 NT/mes | + Featured listings, comisión reducida |

### Modelo `SubscriptionPlan`

```php
// Campos de features (JSON)
$plan->features = [
    'telegram_bot_access' => true,
    'api_access'          => false,
    'featured_listings'   => 5,
    'max_products'        => 50,
    'commission_discount' => 1.0,  // % descuento sobre comisión base
];
```

### `UserSubscription` — Sin auto-renew

```php
$subscription->auto_renew = false;  // siempre false por política
$subscription->expires_at;          // fecha de expiración
$subscription->daysRemaining();     // días restantes

// Verificar si tiene acceso
$user->activeSubscription()->exists()
```
