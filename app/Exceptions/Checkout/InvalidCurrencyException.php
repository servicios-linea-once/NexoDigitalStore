<?php

namespace App\Exceptions\Checkout;

use Exception;

/**
 * Lanzada cuando la moneda especificada para el checkout no es válida
 * o no está configurada en el sistema.
 */
class InvalidCurrencyException extends Exception
{
    public function __construct(private string $currency)
    {
        parent::__construct("Moneda no válida: {$currency}");
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getShortMessage(): string
    {
        return 'La moneda seleccionada no es válida.';
    }
}
