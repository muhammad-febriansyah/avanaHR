<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->string('run_no');
            $table->string('type', 16)->default('regular');
            $table->string('status', 16)->default('draft');
            $table->unsignedBigInteger('gross_total')->default(0);
            $table->unsignedBigInteger('net_total')->default(0);
            $table->unsignedBigInteger('tax_total')->default(0);
            $table->unsignedBigInteger('bpjs_total')->default(0);
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();
            $table->index(['tenant_id', 'period_id'], 'payroll_run_tenant_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
