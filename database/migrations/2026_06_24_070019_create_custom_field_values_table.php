<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('custom_field_id')->constrained('custom_fields')->cascadeOnDelete();
            $table->morphs('customizable');
            $table->json('value')->nullable();
            $table->timestamps();

            $table->index(['custom_field_id', 'customizable_type', 'customizable_id'], 'cfv_field_morph_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
    }
};
