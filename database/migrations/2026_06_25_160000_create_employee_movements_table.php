<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32); // promotion|transfer|demotion|contract_change|suspension|reactivate|resign|terminate
            $table->date('effective_date');
            $table->string('status', 24)->default('draft'); // draft|applied|cancelled
            $table->json('payload_json')->nullable();  // target org assignment overrides
            $table->json('before_json')->nullable();    // snapshot employment lama (diisi saat apply)
            $table->json('after_json')->nullable();      // snapshot employment baru (diisi saat apply)
            $table->text('reason')->nullable();
            $table->boolean('requires_clearance')->default(false);
            $table->foreignId('approval_request_id')->nullable()->constrained('approval_requests')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id', 'effective_date'], 'emp_mov_tenant_emp_eff_idx');
            $table->index(['tenant_id', 'status', 'effective_date'], 'emp_mov_tenant_status_eff_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_movements');
    }
};
