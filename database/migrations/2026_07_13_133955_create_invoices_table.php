<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenancy_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->enum('status', ['belum_dibayar', 'jatuh_tempo', 'menunggu_konfirmasi', 'lunas']);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'owner_id', 'due_date', 'status']);
            $table->unique(['tenancy_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
