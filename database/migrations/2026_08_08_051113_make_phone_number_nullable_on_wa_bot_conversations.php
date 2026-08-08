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
        Schema::table('wa_bot_conversations', function (Blueprint $table) {
            $table->string('phone_number', 30)->nullable()->change();
            $table->string('from_jid', 100)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_bot_conversations', function (Blueprint $table) {
            $table->string('phone_number', 30)->nullable(false)->change();
            $table->string('from_jid', 100)->nullable()->change();
        });
    }
};
