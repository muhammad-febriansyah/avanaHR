<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->unsignedTinyInteger('month');
            $table->year('year');
            $table->date('cutoff_date')->nullable();
            $table->date('pay_date')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'year', 'month'], 'payroll_period_tenant_ym_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
