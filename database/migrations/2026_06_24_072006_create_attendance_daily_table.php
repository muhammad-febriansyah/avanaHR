<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('work_minutes')->default(0);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->string('status', 16)->default('absent');
            $table->boolean('has_correction')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'employee_id', 'date'], 'att_daily_tenant_emp_date_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_daily');
    }
};
