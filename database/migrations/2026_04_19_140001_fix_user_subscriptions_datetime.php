<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert TIMESTAMP → DATETIME for starts_at / expires_at / cancelled_at
     * so they can hold dates beyond the 2038 TIMESTAMP limit.
     * Also make expires_at nullable (Free plan = no expiry).
     */
    public function up(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dateTime('starts_at')->change();
            $table->dateTime('expires_at')->nullable()->change();
            $table->dateTime('cancelled_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->timestamp('starts_at')->change();
            $table->timestamp('expires_at')->change();
            $table->timestamp('cancelled_at')->nullable()->change();
        });
    }
};
