<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_grade_id')->constrained('job_grades')->cascadeOnDelete();
            $table->unsignedBigInteger('band_min')->default(0);
            $table->unsignedBigInteger('band_max')->default(0);
            $table->string('currency', 8)->default('IDR');
            $table->timestamps();
            $table->index(['tenant_id', 'job_grade_id'], 'sal_struct_tenant_grade_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};
