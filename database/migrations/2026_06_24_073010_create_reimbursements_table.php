<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('category', 64);
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('settlement', 16)->default('payroll');
            $table->foreignId('period_id')->nullable()->constrained('payroll_periods')->nullOnDelete();
            $table->string('status', 32)->default('pending');
            $table->foreignId('approval_request_id')->nullable()->constrained('approval_requests')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'employee_id'], 'reimb_tenant_emp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reimbursements');
    }
};
