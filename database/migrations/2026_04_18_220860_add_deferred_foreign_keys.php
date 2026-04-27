<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add deferred foreign keys that had circular dependencies.
 * Runs after all tables are created.
 */
return new class extends Migration
{
    public function up(): void
    {
        // digital_keys → order_items (deferred from create_digital_keys_table)
        Schema::table('digital_keys', function (Blueprint $table) {
            $table->foreign('order_item_id')
                ->references('id')->on('order_items')
                ->nullOnDelete();
        });

        // license_activations already has order_item_id FK in its own migration
        // but we also need to verify order_items → digital_keys via the key field
        // (no FK needed there, it's tracked via order_item_id on digital_keys)
    }

    public function down(): void
    {
        Schema::table('digital_keys', function (Blueprint $table) {
            $table->dropForeign(['order_item_id']);
        });
    }
};
