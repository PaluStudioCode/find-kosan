<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom from_jid di wa_bot_conversations.
 *
 * from_jid menyimpan JID asli pengirim (mis. "628xxx@s.whatsapp.net" atau "173xxx@lid")
 * agar bot bisa membalas ke JID yang benar. Khususnya untuk @lid yang tidak bisa
 * di-normalize ke nomor telepon, balasan harus dikirim ke JID @lid aslinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_bot_conversations', function (Blueprint $table) {
            $table->string('from_jid', 100)->nullable()->after('phone_number');
            $table->index('from_jid');
        });
    }

    public function down(): void
    {
        Schema::table('wa_bot_conversations', function (Blueprint $table) {
            $table->dropIndex(['from_jid']);
            $table->dropColumn('from_jid');
        });
    }
};
