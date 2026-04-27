<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends the welcome notification asynchronously so registration
 * is never slowed down by email/Telegram delivery.
 * Dispatched from AuthController::register().
 */
class SendWelcomeNotification implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;
    public int $backoff = 30;

    public function __construct(public readonly int $userId) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            Log::warning('SendWelcomeNotification: user not found', ['id' => $this->userId]);
            return;
        }

        $user->notify(new WelcomeNotification);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendWelcomeNotification failed', [
            'user_id' => $this->userId,
            'error'   => $e->getMessage(),
        ]);
    }
}
