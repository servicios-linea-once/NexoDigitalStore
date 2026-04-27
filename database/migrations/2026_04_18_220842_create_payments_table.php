<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('gateway', ['paypal', 'mercadopago', 'nexocoins', 'manual']);
            $table->string('gateway_order_id')->nullable();      // PayPal order ID
            $table->string('gateway_transaction_id')->nullable(); // PayPal capture ID / MP payment ID
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->decimal('amount', 14, 4);
            $table->string('currency', 3);
            $table->decimal('amount_usd', 14, 4)->nullable();   // normalized
            $table->decimal('fee', 14, 4)->default(0);
            $table->json('gateway_response')->nullable();        // raw response
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('gateway_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
