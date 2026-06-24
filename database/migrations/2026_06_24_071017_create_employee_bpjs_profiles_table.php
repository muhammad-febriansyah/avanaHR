<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_bpjs_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('effective_date');
            $table->string('bpjs_kesehatan_no', 32)->nullable();
            $table->string('bpjs_tk_no', 32)->nullable();
            $table->unsignedBigInteger('kesehatan_basis')->nullable();
            $table->unsignedBigInteger('tk_basis')->nullable();
            $table->json('participation_flags')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id', 'effective_date'], 'emp_bpjs_tenant_emp_eff_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_bpjs_profiles');
    }
};
