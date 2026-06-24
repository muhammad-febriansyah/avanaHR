<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('menu_key');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'menu_key'], 'user_fav_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_favorites');
    }
};
