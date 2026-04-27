<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'cancelled',
                'disputed',
                'refunded',
            ])->default('pending');
            $table->decimal('subtotal', 14, 4);
            $table->decimal('discount_amount', 14, 4)->default(0);
            $table->decimal('nexocoins_used', 14, 4)->default(0);
            $table->decimal('total', 14, 4);
            $table->string('currency', 3)->default('USD');
            $table->decimal('total_in_currency', 14, 4)->nullable();
            $table->decimal('exchange_rate', 14, 6)->default(1);
            $table->string('payment_method')->nullable();       // paypal, mercadopago, nexocoins
            $table->string('payment_reference')->nullable();    // external transaction id
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['buyer_id', 'status']);
            $table->index('payment_reference');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
