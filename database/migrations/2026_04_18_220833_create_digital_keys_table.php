<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_keys', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->text('key_value');                     // encrypted key/code
            $table->string('key_hash', 64)->nullable();
            $table->enum('status', ['available', 'reserved', 'sold', 'refunded'])->default('available');
            $table->unsignedBigInteger('order_item_id')->nullable(); // FK added after order_items
            $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('reserved_until')->nullable(); // expiry of reservation
            $table->timestamp('sold_at')->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_license')->default(false); // vs key de un solo uso
            $table->unsignedSmallInteger('max_activations')->default(1);
            $table->unsignedTinyInteger('activation_count')->default(0);
            $table->unsignedSmallInteger('current_activations')->default(0);
            $table->timestamp('license_expires_at')->nullable();
            $table->string('license_type', 40)->nullable(); // perpetual, subscription, trial
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['status', 'reserved_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_keys');
    }
};
