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
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->decimal('pph_percent', 5, 2)->default(0)->after('amount');
            $table->decimal('pph_amount', 12, 2)->default(0)->after('pph_percent');
            $table->decimal('net_amount', 12, 2)->default(0)->after('pph_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropColumn(['pph_percent', 'pph_amount', 'net_amount']);
        });
    }
};
