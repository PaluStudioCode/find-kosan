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
        Schema::table('boarding_house_photos', function (Blueprint $table) {
            $table->string('category', 50)->default('lainnya')->after('file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boarding_house_photos', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
