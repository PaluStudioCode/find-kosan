<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('boarding_house_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('category', ['data_kos_tidak_valid', 'kontak_tidak_valid', 'foto_tidak_sesuai', 'lainnya']);
            $table->text('description');
            $table->enum('status', ['menunggu', 'diproses', 'selesai', 'ditolak']);
            $table->foreignId('handled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('handled_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reporter_id', 'boarding_house_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
