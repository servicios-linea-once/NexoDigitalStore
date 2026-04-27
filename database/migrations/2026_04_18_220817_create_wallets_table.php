<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('balance', 18, 4)->default(0);    // NexoCoins balance
            $table->decimal('locked_balance', 18, 4)->default(0); // reserved
            $table->string('currency', 3)->default('NXC');    // NexoCoins
            $table->string('signature', 64)->nullable()->comment('HMAC-SHA256 hash of ulid + balance + locked_balance to prevent DB tampering');
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
