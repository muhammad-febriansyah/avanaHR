<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('effective_date');
            $table->string('scheme', 16)->default('ter');
            $table->json('ter_table')->nullable();
            $table->json('ptkp_table')->nullable();
            $table->json('rates')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'effective_date'], 'tax_param_tenant_eff_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_parameters');
    }
};
