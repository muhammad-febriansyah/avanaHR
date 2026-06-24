<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 32); // workflow|form|field|permission|layout|report
            $table->string('key');
            $table->json('value')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 32)->default('draft'); // draft|published
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'scope', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
