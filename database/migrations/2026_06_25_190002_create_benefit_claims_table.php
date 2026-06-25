<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefit_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_benefit_id')->constrained('employee_benefits')->cascadeOnDelete();
            $table->date('claim_date');
            $table->unsignedBigInteger('amount'); // rupiah dipakai
            $table->string('description');
            $table->string('status', 24)->default('pending'); // pending|approved|rejected
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_benefit_id'], 'benefit_claim_tenant_eb_idx');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benefit_claims');
    }
};
