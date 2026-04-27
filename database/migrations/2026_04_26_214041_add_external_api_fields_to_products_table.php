<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('external_provider')->nullable()->after('status');
            $table->string('external_id')->nullable()->index()->after('external_provider');
            $table->boolean('external_stock_sync')->default(false)->after('external_id');
            $table->timestamp('last_sync_at')->nullable()->after('external_stock_sync');
            
            // Un índice único compuesto para evitar duplicados del mismo proveedor
            $table->unique(['external_provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['external_provider', 'external_id']);
            $table->dropColumn(['external_provider', 'external_id', 'external_stock_sync', 'last_sync_at']);
        });
    }
};
