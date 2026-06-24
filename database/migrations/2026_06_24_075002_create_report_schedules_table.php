<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_id')->constrained('report_definitions')->cascadeOnDelete();
            $table->string('cron');
            $table->json('recipients')->nullable();
            $table->string('format', 16)->default('excel');
            $table->dateTime('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'report_id'], 'report_sched_tenant_report_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
    }
};
