<?php

namespace App\Exceptions\Checkout;

use Exception;

/**
 * Lanzada cuando el usuario no tiene suficiente saldo en su billetera
 * para completar una compra con NexoTokens.
 */
class InsufficientBalanceException extends Exception
{
    public function __construct(
        private float $required,
        private float $available,
    ) {
        parent::__construct(
            "Saldo insuficiente. Requerido: {$required} NT, Disponible: {$available} NT"
        );
    }

    public function getRequired(): float
    {
        return $this->required;
    }

    public function getAvailable(): float
    {
        return $this->available;
    }

    public function getShortMessage(): string
    {
        return 'Saldo NT insuficiente para completar la compra.';
    }
}
