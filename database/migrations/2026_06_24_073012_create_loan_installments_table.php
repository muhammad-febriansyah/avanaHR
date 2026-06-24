<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_id')->constrained('employee_loans')->cascadeOnDelete();
            $table->foreignId('period_id')->nullable()->constrained('payroll_periods')->nullOnDelete();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('status', 16)->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_installments');
    }
};
