<?php

namespace App\Console\Commands;

use App\Services\ExternalInventoryService;
use Illuminate\Console\Command;

class SyncExternalInventory extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'sync:external-inventory';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Sincroniza el catálogo y stock de productos desde la API externa';

    /**
     * Execute the console command.
     */
    public function handle(ExternalInventoryService $service)
    {
        $this->info("Iniciando sincronización externa...");

        $results = $service->syncCatalog();

        if (isset($results['error'])) {
            $this->error("Error: " . $results['error']);
            return 1;
        }

        $this->table(
            ['Categoría', 'Cantidad'],
            [
                ['Actualizados', $results['updated'] ?? 0],
                ['Errores', $results['errors'] ?? 0]
            ]
        );

        $this->info("Sincronización completada.");
        return 0;
    }
}
