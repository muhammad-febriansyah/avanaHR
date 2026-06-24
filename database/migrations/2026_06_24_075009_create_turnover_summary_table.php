<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnover_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->year('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('joined')->default(0);
            $table->unsignedInteger('resigned')->default(0);
            $table->decimal('turnover_rate', 6, 3)->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'year', 'month'], 'turnover_sum_tenant_ym_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnover_summary');
    }
};
