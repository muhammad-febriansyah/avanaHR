<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_cost_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->nullable()->constrained('payroll_periods')->nullOnDelete();
            $table->unsignedBigInteger('gross')->default(0);
            $table->unsignedBigInteger('net')->default(0);
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('bpjs')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'period_id'], 'payroll_sum_tenant_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_cost_summary');
    }
};
