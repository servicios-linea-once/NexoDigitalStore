<?php

namespace App\Observers;

use App\Models\DigitalKey;

class DigitalKeyObserver
{
    public function created(DigitalKey $digitalKey): void
    {
        $this->syncProductStock($digitalKey);
    }

    public function updated(DigitalKey $digitalKey): void
    {
        // Solo sincronizamos si cambió el estado (ej: de available a sold)
        if ($digitalKey->wasChanged('status')) {
            $this->syncProductStock($digitalKey);
        }
    }

    public function deleted(DigitalKey $digitalKey): void
    {
        $this->syncProductStock($digitalKey);
    }

    private function syncProductStock(DigitalKey $digitalKey): void
    {
        $product = $digitalKey->product;
        if (! $product) return;

        // Recalcular stock de este ítem específico (sea variante o padre)
        $product->update([
            'stock_count' => $product->digitalKeys()->where('status', 'available')->count()
        ]);

        // Si es una variante, forzar al padre a recalcular su stock total también
        if ($product->parent_id) {
            $parent = $product->parent;
            if ($parent) {
                $totalStock = $parent->variants()->sum('stock_count');
                $parent->update(['stock_count' => $totalStock]);
            }
        }
    }
}
