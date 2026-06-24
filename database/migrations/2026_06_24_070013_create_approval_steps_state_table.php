<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_steps_state', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('pending'); // pending|approved|rejected|skipped|delegated|escalated
            $table->timestamp('acted_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['request_id', 'step_order']);
            $table->index(['approver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_steps_state');
    }
};
