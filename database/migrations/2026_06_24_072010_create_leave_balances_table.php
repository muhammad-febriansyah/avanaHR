<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->year('year');
            $table->decimal('entitled', 6, 2)->default(0);
            $table->decimal('used', 6, 2)->default(0);
            $table->decimal('pending', 6, 2)->default(0);
            $table->decimal('expired', 6, 2)->default(0);
            $table->decimal('available', 6, 2)->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'employee_id', 'leave_type_id', 'year'], 'leave_bal_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
