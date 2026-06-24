<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_daily_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('present')->default(0);
            $table->unsignedInteger('late')->default(0);
            $table->unsignedInteger('absent')->default(0);
            $table->unsignedInteger('on_leave')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'date'], 'att_sum_tenant_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_daily_summary');
    }
};
