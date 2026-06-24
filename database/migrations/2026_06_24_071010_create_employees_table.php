<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('employee_no');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('gender', 16)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('religion', 32)->nullable();
            $table->string('marital_status', 32)->nullable();
            $table->string('nik_ktp', 32)->nullable();
            $table->string('npwp', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('address')->nullable();
            $table->string('status', 32)->default('probation')->index(); // probation|active|on_leave|suspended|resigned|terminated
            $table->date('join_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'employee_no']);
            $table->unique(['tenant_id', 'nik_ktp']);
            $table->unique(['tenant_id', 'npwp']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
