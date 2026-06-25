<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_movement_id')->constrained('employee_movements')->cascadeOnDelete();
            $table->string('category', 32); // hr|finance|it|asset|legal
            $table->string('label');
            $table->string('status', 16)->default('pending'); // pending|done|waived
            $table->text('note')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_movement_id'], 'clearance_tenant_movement_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_items');
    }
};
