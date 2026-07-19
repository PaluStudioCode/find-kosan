<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boarding_house_id')->constrained()->onDelete('cascade');
            $table->string('name', 100)->nullable();
            $table->string('room_number', 50);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->enum('price_period', ['bulanan', 'tahunan']);
            $table->unsignedInteger('capacity');
            $table->enum('status', ['tersedia', 'terisi', 'tidak_aktif']);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['boarding_house_id', 'room_number']);
            $table->index(['boarding_house_id', 'status', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
