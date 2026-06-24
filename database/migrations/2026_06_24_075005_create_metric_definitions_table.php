<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metric_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('name');
            $table->text('formula')->nullable();
            $table->json('dimension')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'key'], 'metric_def_tenant_key_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_definitions');
    }
};
