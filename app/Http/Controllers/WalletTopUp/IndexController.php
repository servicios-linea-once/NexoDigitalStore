<?php

namespace App\Http\Controllers\WalletTopUp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // OPTIMIZACIÓN: Se eliminó el envío de variables redundantes ($packages, $rate).
        // El frontend ya las maneja de forma local, ahorrando procesamiento al servidor.
        return Inertia::render('Wallet/TopUp', [
            'wallet' => $request->user()->wallet,
        ]);
    }
}
