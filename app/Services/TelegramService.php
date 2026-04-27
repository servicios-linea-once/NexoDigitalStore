<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TelegramService — API wrapper para Bot Telegram
 *
 * Métodos principales:
 *   sendMessage($chatId, $text, $keyboard = null)
 *   sendDocument($chatId, $fileUrl, $caption)
 *   answerCallbackQuery($callbackId, $text)
 *   setWebhook($url)
 */
class TelegramService
{
    protected string $token;

    protected string $baseUrl;

    public function __construct()
    {
        $this->token = config('nexo.telegram.token', config('services.telegram.bot_token', ''));
        $this->baseUrl = "https://api.telegram.org/bot{$this->token}";
    }

    // ── Core sender ────────────────────────────────────────────────────────
    protected function call(string $method, array $params = []): ?array
    {
        if (empty($this->token)) {
            Log::warning('[Telegram] Token no configurado.');

            return null;
        }

        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/{$method}", $params);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("[Telegram] Error en {$method}: ".$e->getMessage());

            return null;
        }
    }

    // ── Mensajes de texto ──────────────────────────────────────────────────
    public function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null): ?array
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->call('sendMessage', $params);
    }

    // ── Editar mensaje ─────────────────────────────────────────────────────
    public function editMessage(int|string $chatId, int $messageId, string $text, ?array $replyMarkup = null): ?array
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->call('editMessageText', $params);
    }

    // ── Callback answer ────────────────────────────────────────────────────
    public function answerCallbackQuery(string $callbackId, string $text = '', bool $showAlert = false): ?array
    {
        return $this->call('answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text' => $text,
            'show_alert' => $showAlert,
        ]);
    }

    // ── Teclados ───────────────────────────────────────────────────────────

    /**
     * Crea un InlineKeyboard con filas de botones.
     * Cada fila es un array de ['text' => '...', 'callback_data' => '...'].
     */
    public static function inlineKeyboard(array $rows): array
    {
        return ['inline_keyboard' => $rows];
    }

    /**
     * Crea un ReplyKeyboard permanente.
     */
    public static function replyKeyboard(array $buttons, bool $resize = true): array
    {
        return [
            'keyboard' => $buttons,
            'resize_keyboard' => $resize,
            'one_time_keyboard' => false,
        ];
    }

    public static function removeKeyboard(): array
    {
        return ['remove_keyboard' => true];
    }

    // ── Registro de webhook ────────────────────────────────────────────────
    public function setWebhook(string $url): ?array
    {
        return $this->setWebhookFull($url);
    }

    public function setWebhookFull(string $url): ?array
    {
        $params = [
            'url'                  => $url,
            'allowed_updates'      => ['message', 'callback_query', 'my_chat_member'],
            'drop_pending_updates' => true,
        ];

        // Include secret token if configured (SEC-02)
        $secret = config('nexo.telegram.webhook_secret', '');
        if (! empty($secret)) {
            $params['secret_token'] = $secret;
        }

        return $this->call('setWebhook', $params);
    }

    public function deleteWebhook(): ?array
    {
        return $this->call('deleteWebhook');
    }

    public function getWebhookInfo(): ?array
    {
        return $this->call('getWebhookInfo');
    }

    public function getMe(): ?array
    {
        return $this->call('getMe');
    }

    // ── Enviar foto (URL pública) ──────────────────────────────────────────
    public function sendPhoto(int|string $chatId, string $photoUrl, string $caption = '', ?array $replyMarkup = null): ?array
    {
        $params = [
            'chat_id' => $chatId,
            'photo' => $photoUrl,
            'caption' => $caption,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->call('sendPhoto', $params);
    }
}
