<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unsignedBigInteger('employee_id')->nullable()->after('tenant_id')->index();
            $table->string('status', 32)->default('active')->after('password')->index();
            $table->timestamp('last_login_at')->nullable()->after('status');

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['employee_id', 'status', 'last_login_at']);
        });
    }
};
