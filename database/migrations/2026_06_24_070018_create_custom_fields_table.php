<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type'); // model key, e.g. employee
            $table->string('key');
            $table->string('label');
            $table->string('type', 32)->default('string'); // string|number|date|boolean|select
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'entity_type', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
