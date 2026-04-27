<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('telegram_id', 20)->unique();
            $table->string('username', 80)->nullable();
            $table->string('first_name', 80)->nullable();
            $table->string('last_name', 80)->nullable();
            $table->string('language_code', 10)->default('es');
            $table->boolean('is_linked')->default(false);      // linked to account
            $table->string('link_token', 64)->nullable();      // one-time link token
            $table->timestamp('link_token_expires_at')->nullable();
            $table->string('preferred_currency', 3)->default('USD');
            $table->enum('state', [                            // bot conversation state
                'idle', 'browsing', 'checkout', 'awaiting_payment',
            ])->default('idle');
            $table->json('cart')->nullable();                  // bot cart state
            $table->boolean('notifications_enabled')->default(true);
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();

            $table->index('telegram_id');
            $table->index('link_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_users');
    }
};
