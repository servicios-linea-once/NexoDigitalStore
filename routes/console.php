<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Rutas de Consola
|--------------------------------------------------------------------------
|
| En este archivo es donde puede definir todos los comandos de consola
| basados en Closures. Cada Closure está vinculado a una instancia de
| comando, permitiendo interactuar fácilmente con los métodos IO.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Muestra una cita inspiradora');

// ── Scheduled Maintenance ───────────────────────────────────────────────────
use Illuminate\Support\Facades\Schedule;

/**
 * Liberar claves reservadas cuya ventana de pago expiró (ejecución de comando artisan)
 * Se ejecuta cada 5 minutos y evita ejecuciones superpuestas para prevenir condiciones de carrera.
 */
Schedule::command('keys:release-reservations')->everyFiveMinutes()->withoutOverlapping();

// ── [PUNTO-3] Liberar claves reservadas cuyo tiempo expiró ──────────────────
// Devuelve la clave al stock (status=available) y restaura el stock_count del producto.
// Se ejecuta cada 5 minutos para que los clientes no queden bloqueados por pagos abandonados.
Schedule::call(function () {
    // Recuperar todas las reservas de claves digitales expiradas, precargando su orden y artículos asociados
    $expired = \App\Models\DigitalKey::expiredReservations()->with('orderItem.order')->get();

    $released = 0;
    foreach ($expired as $key) {
        // Procesar cada clave expirada dentro de una transacción para asegurar la integridad de los datos
        DB::transaction(function () use ($key, &$released) {
            // Restaurar la clave a disponible y limpiar todos los metadatos de la reserva
            $key->update([
                'status'          => 'available',
                'order_item_id'   => null,
                'reserved_at'     => null,
                'reserved_until'  => null,
                'reserved_by'     => null,
            ]);

            // Si la clave está vinculada a un producto, reponer el stock disponible del producto
            if ($key->product_id) {
                \App\Models\Product::where('id', $key->product_id)->increment('stock_count');
            }

            // Cancelar la orden si aún está pendiente (evitar órdenes zombi)
            $order = $key->orderItem?->order;
            if ($order && $order->status === 'pending') {
                // Verificar si la orden tiene otras claves activas (no disponibles) aún asociadas
                $orderHasOtherActiveKeys = $order->items()
                    ->whereHas('digitalKey', fn ($q) => $q->where('status', '!=', 'available'))
                    ->exists();

                // Si no existen otras claves activas, la orden completa se considera abandonada y se cancela
                if (! $orderHasOtherActiveKeys) {
                    $order->update(['status' => 'cancelled']);
                    Log::info("[Scheduler] Orden #{$order->ulid} cancelada por reserva expirada.");
                }
            }

            $released++;
        });
    }

    // Registrar el número total de claves liberadas exitosamente en este lote
    if ($released > 0) {
        Log::info("[Scheduler] Claves reservadas liberadas: {$released}");
    }
})->everyFiveMinutes()->name('release-expired-keys')->withoutOverlapping()->onOneServer();

// ── [PUNTO-3] Limpiar tokens de vinculación Telegram expirados ──────────────
// Seguridad: elimina telegram_link_token expirados del usuario web.
// Previene que tokens de vinculación antiguos sean reutilizados por terceros.
// Se ejecuta cada hora — los tokens tienen TTL de 15 min por diseño.
Schedule::call(function () {
    // Consultar la tabla users por tokens expirados y anularlos
    $cleaned = DB::table('users')
        ->whereNotNull('telegram_link_token')
        ->where('telegram_link_token_expires_at', '<', now())
        ->update([
            'telegram_link_token' => null,
            'telegram_link_token_expires_at' => null,
        ]);

    // Registrar la cantidad de tokens limpiados para propósitos de auditoría y monitoreo
    if ($cleaned > 0) {
        Log::info("[Scheduler] Tokens Telegram expirados limpiados: {$cleaned}");
    }
})->hourly()->name('clean-expired-telegram-tokens')->withoutOverlapping()->onOneServer();

// ── [PUNTO-1] escrow:auto-release → no-op (conservado por compatibilidad) ───
// El comando existe como no-op si algún proceso legacy lo invoca.

/**
 * Expirar Suscripciones
 * Expira las suscripciones de usuarios que han pasado su fecha de finalización.
 * Se ejecuta diariamente a las 00:05.
 */
Schedule::command('subscriptions:expire')->dailyAt('00:05')->withoutOverlapping();

/**
 * Verificar Cadena de Billeteras
 * Verifica la integridad criptográfica de todas las cadenas de transacciones de las billeteras.
 * Se ejecuta cada noche a las 03:00 en segundo plano para evitar bloquear otros procesos.
 */
Schedule::command('wallet:verify-chain')->dailyAt('03:00')->withoutOverlapping()->runInBackground();

/**
 * Comandos Heredados / Existentes
 * - orders:expire-pending: Limpia órdenes pendientes cada 5 minutos.
 * - nexo:audit-ledger: Audita el libro mayor en busca de discrepancias diariamente a la medianoche.
 */
Schedule::command('orders:expire-pending')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('nexo:audit-ledger')->dailyAt('00:00')->withoutOverlapping();
