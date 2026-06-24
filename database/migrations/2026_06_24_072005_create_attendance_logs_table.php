<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type', 8);
            $table->dateTime('logged_at');
            $table->string('source', 16);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('face_confidence', 4, 3)->nullable();
            $table->string('device_id')->nullable();
            $table->dateTime('offline_captured_at')->nullable();
            $table->boolean('is_suspicious')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id', 'logged_at'], 'att_logs_tenant_emp_loggedat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
