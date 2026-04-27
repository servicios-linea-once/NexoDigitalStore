<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_activations', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('digital_key_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // buyer
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();

            // Datos de la máquina / dispositivo
            $table->string('machine_id')->nullable();           // fingerprint del dispositivo
            $table->string('machine_name', 120)->nullable();    // "PC de trabajo", "Laptop gaming"
            $table->string('os', 80)->nullable();               // Windows 11, macOS 15, Android 14
            $table->string('device_type', 40)->nullable();      // desktop, laptop, mobile, tablet
            $table->string('ip_address', 45)->nullable();
            $table->string('hostname', 120)->nullable();

            // Estado de la licencia
            $table->enum('status', ['active', 'deactivated', 'expired', 'revoked'])->default('active');
            $table->timestamp('activated_at');
            $table->timestamp('expires_at')->nullable();        // null = permanente
            $table->timestamp('deactivated_at')->nullable();
            $table->string('deactivation_reason')->nullable();

            // Para API Flutter
            $table->text('activation_token')->nullable();       // token único por activación (encrypted)
            $table->timestamp('last_seen_at')->nullable();      // último heartbeat de la app

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['digital_key_id', 'status']);
            $table->index('machine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_activations');
    }
};
