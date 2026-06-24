<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('approval_flows')->cascadeOnDelete();
            $table->unsignedInteger('order');
            $table->string('mode', 16)->default('sequential'); // sequential|parallel
            $table->string('approver_type', 32); // role|specific_user|manager_of_requester|manager_level_n|dynamic_field
            $table->string('approver_ref')->nullable();
            $table->unsignedInteger('sla_hours')->nullable();
            $table->string('escalate_to')->nullable();
            $table->boolean('allow_delegate')->default(true);
            $table->unsignedInteger('min_approvals')->default(1);
            $table->timestamps();

            $table->index(['flow_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_flow_steps');
    }
};
