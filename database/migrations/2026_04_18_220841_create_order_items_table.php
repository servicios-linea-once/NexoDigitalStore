<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->string('product_name', 200);         // snapshot at purchase
            $table->string('product_cover')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('total_price', 14, 4);
            $table->decimal('cashback_amount', 14, 4)->default(0);
            $table->enum('delivery_status', ['pending', 'delivered', 'failed'])->default('pending');
            $table->enum('delivery_type', ['automatic', 'manual', 'api'])->default('automatic');
            $table->unsignedTinyInteger('delivery_attempts')->default(0);
            $table->text('delivery_note')->nullable();
            $table->boolean('seller_paid')->default(false);
            $table->timestamp('seller_paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['seller_id', 'delivery_status', 'delivery_type'], 'idx_items_delivery');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
