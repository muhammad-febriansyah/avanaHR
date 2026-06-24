<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('effective_date');
            $table->string('ptkp_status', 8)->nullable(); // TK/0 .. K/3
            $table->string('npwp', 32)->nullable();
            $table->string('tax_method', 16)->default('ter'); // ter|gross|nett
            $table->unsignedBigInteger('beginning_ytd')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id', 'effective_date'], 'emp_tax_tenant_emp_eff_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_tax_profiles');
    }
};
