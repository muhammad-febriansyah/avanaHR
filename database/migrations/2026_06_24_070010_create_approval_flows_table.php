<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_type')->index(); // leave|overtime|claim|payroll|...
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable(); // grade/amount/department/location rules
            $table->timestamps();

            $table->index(['tenant_id', 'transaction_type', 'is_active'], 'approval_flows_tenant_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_flows');
    }
};
