<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id');
            $table->string('platform', 16)->nullable();
            $table->string('fcm_token')->nullable();
            $table->boolean('biometric_enabled')->default(false);
            $table->dateTime('last_seen')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'device_id'], 'device_reg_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_registrations');
    }
};
