<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->dateTime('planned_start')->nullable();
            $table->dateTime('planned_end')->nullable();
            $table->unsignedInteger('planned_minutes')->default(0);
            $table->unsignedInteger('actual_minutes')->default(0);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('payroll_component_id')->nullable()->index();
            $table->string('status', 32)->default('pending');
            $table->foreignId('approval_request_id')->nullable()->constrained('approval_requests')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id'], 'ot_req_tenant_emp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};
