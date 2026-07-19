<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activation_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('token_hash', 255);
            $table->enum('purpose', ['tenant_activation']);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['user_id', 'purpose', 'expires_at', 'used_at'], 'uat_uid_purpose_expires_used_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activation_tokens');
    }
};
