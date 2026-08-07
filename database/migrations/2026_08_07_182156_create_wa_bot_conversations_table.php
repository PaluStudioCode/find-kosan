<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_bot_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('from_jid', 100)->unique();
            $table->string('phone_number', 30)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('identified_role', 20)->default('public');
            $table->text('context_summary')->nullable();
            $table->boolean('is_bot_enabled')->default(true);
            $table->unsignedInteger('total_messages')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_bot_conversations');
    }
};
