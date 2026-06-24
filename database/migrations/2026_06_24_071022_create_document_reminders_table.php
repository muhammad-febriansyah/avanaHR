<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_document_id')->constrained('employee_documents')->cascadeOnDelete();
            $table->date('remind_at');
            $table->string('status', 32)->default('pending')->index(); // pending|sent
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'remind_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_reminders');
    }
};
