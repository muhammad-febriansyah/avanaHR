<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 64); // contract|ktp|ijazah|...
            $table->string('number')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expired_at')->nullable();
            $table->unsignedInteger('reminder_days')->nullable();
            $table->string('access_level', 32)->default('hr'); // hr|manager|employee
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id']);
            $table->index(['tenant_id', 'expired_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
