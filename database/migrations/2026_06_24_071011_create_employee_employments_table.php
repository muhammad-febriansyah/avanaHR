<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_employments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_grade_id')->nullable()->constrained('job_grades')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('work_calendar_id')->nullable()->constrained('work_calendars')->nullOnDelete();
            $table->string('employment_type', 32)->default('permanent'); // permanent|contract|intern|outsource
            $table->string('status', 32)->default('active');
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id', 'effective_date'], 'emp_employ_tenant_emp_eff_idx');
            $table->index(['tenant_id', 'manager_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_employments');
    }
};
