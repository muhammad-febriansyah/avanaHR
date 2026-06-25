<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('destination');
            $table->text('purpose');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('transport_mode', 64)->nullable(); // mobil|pesawat|kereta|dll
            $table->unsignedBigInteger('estimated_cost')->nullable(); // rupiah
            $table->string('status', 24)->default('pending'); // pending|approved|rejected|cancelled
            $table->text('notes')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'employee_id', 'start_date'], 'work_visit_tenant_emp_start_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_visits');
    }
};
