<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_steps_state', function (Blueprint $table) {
            // SLA deadline for the current step + escalation lifecycle markers
            // set by the approvals:check-sla command.
            $table->timestamp('due_at')->nullable()->after('status');
            $table->timestamp('reminded_at')->nullable()->after('due_at');
            $table->timestamp('escalated_at')->nullable()->after('reminded_at');

            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::table('approval_steps_state', function (Blueprint $table) {
            $table->dropIndex(['due_at']);
            $table->dropColumn(['due_at', 'reminded_at', 'escalated_at']);
        });
    }
};
