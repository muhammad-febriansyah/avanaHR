<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32); // hire|onboard|mutation|promotion|demotion|suspension|resign|terminate
            $table->date('effective_date');
            $table->json('from_json')->nullable();
            $table->json('to_json')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('approval_request_id')->nullable()->constrained('approval_requests')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id', 'effective_date'], 'emp_lifecycle_tenant_emp_eff_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_lifecycle_events');
    }
};
