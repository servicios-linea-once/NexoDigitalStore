<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * [PUNTO-4] Canal privado de órdenes — autenticación Sanctum/Reverb.
 *
 * El cliente Vue 3 se suscribe a: private-order.{userId}
 * Solo el propio buyer puede escuchar su canal.
 */
Broadcast::channel('order.{buyerId}', function ($user, int $buyerId) {
    // Solo el comprador autenticado puede escuchar su propio canal
    return (int) $user->id === $buyerId;
});
