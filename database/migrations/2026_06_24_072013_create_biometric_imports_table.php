<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('device_id')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('success')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'biometric_imp_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_imports');
    }
};
