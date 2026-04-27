<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();           // USD, PEN, COP, MXN, ARS
            $table->string('name', 60);                    // US Dollar
            $table->string('symbol', 8);                   // $, S/, $
            $table->decimal('rate_to_usd', 14, 6)->default(1.0); // conversion base
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
