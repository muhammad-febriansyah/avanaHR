<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 32); // approve|reject|revise|delegate|escalate
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['request_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};
