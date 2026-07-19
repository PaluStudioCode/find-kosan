<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->string('file_path', 255);
            $table->string('caption', 150)->nullable();
            $table->unsignedInteger('sort_order');
            $table->boolean('is_primary');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['room_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_photos');
    }
};
