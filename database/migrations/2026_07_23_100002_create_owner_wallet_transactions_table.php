<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('withdrawal_request_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->timestamps();

            $table->index(['owner_wallet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_wallet_transactions');
    }
};
