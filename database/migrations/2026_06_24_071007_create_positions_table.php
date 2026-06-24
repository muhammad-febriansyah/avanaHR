<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_level_id')->nullable()->constrained('job_levels')->nullOnDelete();
            $table->foreignId('job_grade_id')->nullable()->constrained('job_grades')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
