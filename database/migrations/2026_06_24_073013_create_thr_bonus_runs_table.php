<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thr_bonus_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('period_ref')->nullable();
            $table->json('formula')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->index(['tenant_id', 'type'], 'thr_bonus_tenant_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thr_bonus_runs');
    }
};
