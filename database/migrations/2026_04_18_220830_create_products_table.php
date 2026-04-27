<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('parent_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 200);
            $table->string('variant_name')->nullable();
            $table->string('slug', 220)->unique();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('platform', 60)->nullable();     // Steam, PSN, Xbox, Nintendo...
            $table->string('region', 60)->nullable();        // Global, LATAM, US, EU...
            $table->enum('delivery_type', ['auto', 'manual', 'api'])->default('auto');
            $table->text('activation_guide')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'sold_out'])->default('draft');
            $table->decimal('price_usd', 14, 2)->default(0);
            $table->decimal('price_pen', 14, 2)->default(0);
            $table->decimal('cashback_percent', 5, 2)->default(0);
            $table->unsignedInteger('cashback_amount_nt')->default(0);
            $table->unsignedInteger('stock_count')->default(0);  // cache del stock
            $table->unsignedTinyInteger('max_activations_per_key')->default(1);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_preorder')->default(false);
            $table->date('preorder_release_date')->nullable();
            $table->unsignedBigInteger('total_sales')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->unsignedInteger('rating_count')->default(0);
            $table->json('tags')->nullable();
            $table->json('meta')->nullable();               // SEO, extra data
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status', 'is_featured']);
            $table->index(['seller_id', 'status']);
            $table->index(['platform', 'region']);
            $table->index('total_sales');
            if (config('database.default') !== 'sqlite' && config('database.connections.' . config('database.default') . '.driver') !== 'sqlite') {
                $table->fullText(['name', 'description']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
