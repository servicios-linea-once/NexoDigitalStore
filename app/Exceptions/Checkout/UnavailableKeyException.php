<?php

namespace App\Exceptions\Checkout;

use Exception;

/**
 * Lanzada cuando no hay claves digitales disponibles para vender
 * un producto durante el proceso de checkout.
 */
class UnavailableKeyException extends Exception
{
    public function __construct(private string $productName)
    {
        parent::__construct("Sin clave digital disponible: {$productName}");
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getShortMessage(): string
    {
        return "Sin clave disponible: {$this->productName}";
    }
}
