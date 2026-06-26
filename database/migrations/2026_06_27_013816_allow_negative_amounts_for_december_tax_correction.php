<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The December annual PPh 21 correction can be a refund (negative), so
        // tax/deductions/net and the corresponding line can legitimately go
        // negative. gross + BPJS remain non-negative.
        Schema::table('payslips', function (Blueprint $table) {
            $table->bigInteger('deductions')->default(0)->change();
            $table->bigInteger('tax')->default(0)->change();
            $table->bigInteger('net')->default(0)->change();
        });

        Schema::table('payslip_lines', function (Blueprint $table) {
            $table->bigInteger('amount')->default(0)->change();
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            // Run totals aggregate the (possibly negative) December correction.
            $table->bigInteger('tax_total')->default(0)->change();
            $table->bigInteger('net_total')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->unsignedBigInteger('deductions')->default(0)->change();
            $table->unsignedBigInteger('tax')->default(0)->change();
            $table->unsignedBigInteger('net')->default(0)->change();
        });

        Schema::table('payslip_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->default(0)->change();
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_total')->default(0)->change();
            $table->unsignedBigInteger('net_total')->default(0)->change();
        });
    }
};
