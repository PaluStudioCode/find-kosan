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
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('rent_price', 12, 2)->default(0)->after('period_end');
            $table->decimal('ppn_percent', 5, 2)->default(0)->after('rent_price');
            $table->decimal('ppn_amount', 12, 2)->default(0)->after('ppn_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['rent_price', 'ppn_percent', 'ppn_amount']);
        });
    }
};
