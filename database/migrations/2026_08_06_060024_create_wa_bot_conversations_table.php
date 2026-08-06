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
        Schema::create('wa_bot_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 30)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('identified_role', ['user', 'admin', 'super_admin', 'public'])->default('public');
            $table->text('context_summary')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->boolean('is_bot_enabled')->default(true);
            $table->unsignedInteger('total_messages')->default(0);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_bot_conversations');
    }
};
