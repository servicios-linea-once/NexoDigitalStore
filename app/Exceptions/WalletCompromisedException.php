<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class WalletCompromisedException extends Exception
{
    public function __construct(string $ulid)
    {
        parent::__construct("Sello criptográfico inválido para la billetera [{$ulid}]. La billetera ha sido bloqueada por seguridad.");
    }

    /**
     * Report the exception.
     */
    public function report(): void
    {
        Log::critical("SECURITY ALERT: Intento de uso de billetera comprometida o alterada.", [
            'exception' => $this,
        ]);
    }
}
