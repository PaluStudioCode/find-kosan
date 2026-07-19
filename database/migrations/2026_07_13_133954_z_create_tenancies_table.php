<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('boarding_house_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('occupant_count')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['aktif', 'selesai', 'nonaktif']);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'owner_id', 'boarding_house_id', 'room_id', 'status'], 'tenancies_tenant_owner_bh_room_status_idx');
            $table->index(['room_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenancies');
    }
};
