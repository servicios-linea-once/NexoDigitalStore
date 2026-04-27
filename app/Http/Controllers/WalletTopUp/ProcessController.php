<?php

namespace App\Http\Controllers\WalletTopUp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\TopUpRequest;
use App\Models\Order;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;

class ProcessController extends Controller
{
    public function __invoke(TopUpRequest $request, WalletService $walletService): RedirectResponse
    {
        $user     = $request->user();
        $ntAmount = (int) $request->nt_amount;

        // OPTIMIZACIÓN 1: Reutilizamos tu WalletService existente (Principio DRY)
        $walletService->ensureWallet($user);

        // OPTIMIZACIÓN 2: Seguridad y corrección del Bug de Bonos.
        // Calculamos los extras en el backend para que los usuarios reciban sus regalos.
        $rate     = config('nexo.token.rate_to_usd', 0.01);
        $usdTotal = $ntAmount * $rate;
        $bonus    = $this->calculateBonus($ntAmount);

        $order = Order::create([
            'buyer_id'          => $user->id,
            'status'            => 'pending',
            'currency'          => 'USD',
            'subtotal'          => $usdTotal,
            'discount_amount'   => 0,
            'nexocoins_used'    => 0,
            'total'             => $usdTotal,
            'total_in_currency' => $usdTotal,
            'exchange_rate'     => 1.0,
            'payment_method'    => $request->payment_method,
            'ip_address'        => $request->ip(),
            'meta'              => [
                'is_topup'  => true,
                // Guardamos el monto total (Base + Bono) para que se acredite correctamente
                'nt_amount' => $ntAmount + $bonus,
            ],
        ]);

        return redirect()->route('orders.show', $order->ulid);
    }

    /**
     * Calcula el bonus de NT según los paquetes de la tienda.
     * Es más seguro tener esto en el backend.
     */
    private function calculateBonus(int $ntAmount): int
    {
        return match ($ntAmount) {
            500   => 25,
            1000  => 75,
            2500  => 250,
            5000  => 800,
            10000 => 2000,
            default => 0,
        };
    }
}
