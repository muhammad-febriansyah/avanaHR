<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_visit_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_visit_id')->constrained('work_visits')->cascadeOnDelete();
            $table->date('visited_at');
            $table->string('location');
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable(); // bukti kunjungan (link/path)
            $table->timestamps();

            $table->index(['tenant_id', 'work_visit_id'], 'work_visit_report_tenant_visit_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_visit_reports');
    }
};
