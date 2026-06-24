<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->string('bank_code', 32);
            $table->string('format', 32)->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('total')->default(0);
            $table->string('exception_report_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_files');
    }
};
