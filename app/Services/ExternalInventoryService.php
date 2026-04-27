<?php

namespace App\Services;

use App\Models\Product;
use App\Models\DigitalKey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalInventoryService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $providerName = 'external_api_v1'; // Nombre identificador

    public function __construct()
    {
        $this->baseUrl = config('services.external_api.base_url');
        $this->apiKey  = config('services.external_api.key');
    }

    /**
     * Sincroniza la lista completa de productos desde la API.
     */
    public function syncCatalog(): array
    {
        try {
            // Ejemplo de llamada a la API
            $response = Http::withToken($this->apiKey)->get("{$this->baseUrl}/products");

            if ($response->failed()) {
                throw new \Exception("Error al conectar con la API externa: " . $response->body());
            }

            $externalProducts = $response->json();
            $results = ['created' => 0, 'updated' => 0, 'errors' => 0];

            foreach ($externalProducts as $item) {
                try {
                    $this->processProduct($item);
                    $results['updated']++;
                } catch (\Exception $e) {
                    Log::error("Error procesando producto externo ID {$item['id']}: " . $e->getMessage());
                    $results['errors']++;
                }
            }

            return $results;
        } catch (\Exception $e) {
            Log::error("Fallo crítico en syncCatalog: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Procesa o actualiza un producto individual.
     */
    protected function processProduct(array $data): void
    {
        // Buscamos si ya existe por external_id
        $product = Product::updateOrCreate(
            [
                'external_provider' => $this->providerName,
                'external_id'       => $data['id'],
            ],
            [
                // Aquí mapeas los campos según tu DB
                // 'name'         => $data['title'],
                // 'price_usd'    => $data['price'],
                'stock_count'     => $data['stock'],
                'last_sync_at'    => now(),
                'external_stock_sync' => true,
            ]
        );
    }

    /**
     * Obtiene llaves (keys) para un producto específico.
     * Útil si las llaves se piden al momento de la venta.
     */
    public function fetchKeysForProduct(Product $product, int $quantity = 1): bool
    {
        if (!$product->external_id) return false;

        try {
            $response = Http::withToken($this->apiKey)
                ->post("{$this->baseUrl}/products/{$product->external_id}/keys", [
                    'quantity' => $quantity
                ]);

            if ($response->successful()) {
                $keys = $response->json()['keys'] ?? [];
                foreach ($keys as $keyCode) {
                    DigitalKey::create([
                        'product_id' => $product->id,
                        'key_content' => $keyCode,
                        'status' => 'available'
                    ]);
                }
                return true;
            }
        } catch (\Exception $e) {
            Log::error("Error al obtener keys externas para producto {$product->id}: " . $e->getMessage());
        }

        return false;
    }
}
