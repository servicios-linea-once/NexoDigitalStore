<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 18, 4);
            $table->decimal('balance_after', 18, 4);
            $table->enum('reason', [
                'purchase',
                'cashback',
                'refund',
                'topup',
                'withdrawal',
                'bonus',
                'adjustment',
            ]);
            $table->string('reference')->nullable(); // order ulid, etc.
            $table->text('note')->nullable();
            $table->string('previous_hash', 64)->nullable()->comment('Hash of the previous transaction to form an immutable chain');
            $table->string('hash', 64)->nullable()->comment('HMAC-SHA256 hash of ulid + user_id + amount + balance_after + previous_hash');
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
            $table->index('reference');
            $table->index('hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
