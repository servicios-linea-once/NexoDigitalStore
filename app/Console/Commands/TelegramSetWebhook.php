<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook
                                {--delete  : Eliminar el webhook actual}
                                {--info    : Mostrar info del webhook actual}
                                {--url=    : URL personalizada (default: TELEGRAM_WEBHOOK_URL en .env)}';

    protected $description = 'Registra, elimina o inspecciona el webhook del bot de Telegram';

    public function handle(TelegramService $tg): int
    {
        // Mostrar info
        if ($this->option('info')) {
            $info = $tg->getWebhookInfo();
            $this->info('Webhook info actual:');
            $this->line(json_encode($info['result'] ?? $info, JSON_PRETTY_PRINT));

            return 0;
        }

        // Eliminar webhook
        if ($this->option('delete')) {
            $result = $tg->deleteWebhook();
            $result['ok']
                ? $this->info('✅ Webhook eliminado correctamente.')
                : $this->error('❌ Error al eliminar: '.($result['description'] ?? 'unknown'));

            return 0;
        }

        // Registrar webhook
        $url = $this->option('url') ?: env('TELEGRAM_WEBHOOK_URL');

        if (! $url) {
            $this->error('No se encontró TELEGRAM_WEBHOOK_URL en .env. Usa --url=https://tu-ngrok.ngrok-free.app/webhook/telegram');

            return 1;
        }

        $this->info("Registrando webhook en: {$url}");

        $result = $tg->setWebhookFull($url);

        if ($result['ok'] ?? false) {
            $this->info('✅ Webhook registrado correctamente.');

            $info = $tg->getWebhookInfo();
            if (isset($info['result'])) {
                $this->table(['Campo', 'Valor'], [
                    ['URL',              $info['result']['url'] ?? '-'],
                    ['Pending updates',  $info['result']['pending_update_count'] ?? 0],
                    ['Max connections',  $info['result']['max_connections'] ?? '-'],
                    ['Last error',       $info['result']['last_error_message'] ?? 'Ninguno'],
                ]);
            }
        } else {
            $this->error('❌ Error: '.($result['description'] ?? json_encode($result)));

            return 1;
        }

        return 0;
    }
}
