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
        Schema::create('wa_bot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('wa_bot_conversations')->cascadeOnDelete();
            $table->enum('role', ['system', 'user', 'assistant', 'tool']);
            $table->text('content')->nullable();
            $table->string('tool_call_id')->nullable();
            $table->string('tool_name')->nullable();
            $table->json('tool_calls')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->string('model_used')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_bot_messages');
    }
};
