<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RecordAuditLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $event,
        public ?int $userId,
        public array $properties = [],
        public ?int $targetUserId = null // Útil para limpiar la caché del usuario afectado
    ) {}

    public function handle(): void
    {
        AuditLog::record($this->event, $this->userId, $this->properties);

        // Si el evento afecta a un usuario específico, limpiamos su caché de auditoría
        if ($this->targetUserId) {
            Cache::forget("user.{$this->targetUserId}.audit_logs");
        }
    }
}
