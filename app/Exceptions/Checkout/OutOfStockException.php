<?php

namespace App\Exceptions\Checkout;

use Exception;

/**
 * Lanzada cuando un producto no tiene stock disponible
 * en el momento de procesar una compra.
 */
class OutOfStockException extends Exception
{
    public function __construct(private string $productName)
    {
        parent::__construct("Producto sin stock: {$productName}");
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getShortMessage(): string
    {
        return "Sin stock: {$this->productName}";
    }
}
