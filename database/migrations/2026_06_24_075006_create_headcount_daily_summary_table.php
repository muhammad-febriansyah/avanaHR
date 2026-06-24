<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('headcount_daily_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->unsignedInteger('headcount')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'date'], 'headcount_sum_tenant_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('headcount_daily_summary');
    }
};
