<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bpjs_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('effective_date');
            $table->decimal('kes_rate_employee', 8, 4)->default(0);
            $table->decimal('kes_rate_employer', 8, 4)->default(0);
            $table->unsignedBigInteger('kes_cap')->default(0);
            $table->json('tk_rates')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'effective_date'], 'bpjs_param_tenant_eff_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bpjs_parameters');
    }
};
