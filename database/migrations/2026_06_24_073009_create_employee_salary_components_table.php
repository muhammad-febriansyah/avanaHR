<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('payroll_components')->cascadeOnDelete();
            $table->date('effective_date');
            $table->unsignedBigInteger('amount')->default(0);
            $table->decimal('rate', 8, 4)->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'employee_id', 'effective_date'], 'esc_tenant_emp_eff_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_components');
    }
};
