<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boarding_house_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boarding_house_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->timestamps();

            $table->unique(['boarding_house_id', 'user_id']);
            $table->index(['boarding_house_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boarding_house_reviews');
    }
};
