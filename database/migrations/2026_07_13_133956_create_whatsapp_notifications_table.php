<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
            $table->string('phone_number', 30);
            $table->enum('message_type', ['pengingat_jatuh_tempo', 'pembayaran_baru', 'pembayaran_dikonfirmasi']);
            $table->text('message_body')->nullable();
            $table->date('scheduled_date');
            $table->enum('status', ['belum_dikirim', 'terkirim', 'gagal', 'tidak_dikirim']);
            $table->timestamp('sent_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status', 'sent_at']);
            $table->unique(['invoice_id', 'message_type', 'scheduled_date'], 'wa_notif_inv_type_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_notifications');
    }
};
