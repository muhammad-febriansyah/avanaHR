<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('principal')->default(0);
            $table->unsignedInteger('tenor_months')->default(0);
            $table->unsignedBigInteger('installment')->default(0);
            $table->unsignedBigInteger('outstanding')->default(0);
            $table->foreignId('start_period_id')->nullable()->constrained('payroll_periods')->nullOnDelete();
            $table->string('status', 32)->default('pending');
            $table->foreignId('approval_request_id')->nullable()->constrained('approval_requests')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'employee_id'], 'loan_tenant_emp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
