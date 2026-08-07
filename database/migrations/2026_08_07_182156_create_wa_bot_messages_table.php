<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_bot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('wa_bot_conversations')->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content')->nullable();
            $table->string('tool_call_id', 100)->nullable();
            $table->string('tool_name', 100)->nullable();
            $table->json('tool_calls')->nullable();
            $table->unsignedInteger('tokens_used')->default(0);
            $table->string('model_used', 100)->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_bot_messages');
    }
};
