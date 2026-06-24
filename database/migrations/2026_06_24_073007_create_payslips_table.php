<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->json('snapshot')->nullable();
            $table->unsignedBigInteger('gross')->default(0);
            $table->unsignedBigInteger('deductions')->default(0);
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('bpjs_employee')->default(0);
            $table->unsignedBigInteger('bpjs_company')->default(0);
            $table->unsignedBigInteger('net')->default(0);
            $table->string('file_path')->nullable();
            $table->boolean('is_access_protected')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'employee_id'], 'payslip_tenant_emp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
