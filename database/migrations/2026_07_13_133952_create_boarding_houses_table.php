<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boarding_houses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('name', 150);
            $table->text('description');
            $table->text('address');
            $table->string('public_contact_name', 150)->nullable();
            $table->string('public_contact_whatsapp_number', 30)->nullable();
            $table->text('payment_instructions')->nullable();
            $table->string('payment_qris_image_path', 255)->nullable();
            $table->boolean('payment_proof_required')->default(true);
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('subdistrict', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('status', ['draft', 'menunggu_verifikasi', 'dipublikasikan', 'ditolak', 'nonaktif']);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('verification_note')->nullable();
            $table->json('pending_revisions')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'status']);
            $table->index(['city', 'district', 'subdistrict']);
            $table->index(['latitude', 'longitude']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boarding_houses');
    }
};
