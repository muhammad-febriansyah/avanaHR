<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kiosk_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('device_id');
            $table->string('name')->nullable();
            $table->json('allowed_features')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id'], 'kiosk_tenant_branch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosk_devices');
    }
};
