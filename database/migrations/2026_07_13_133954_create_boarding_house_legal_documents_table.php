<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boarding_house_legal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boarding_house_id')->constrained()->onDelete('cascade');
            $table->enum('document_type', ['identitas_pemilik_pengelola', 'bukti_kepemilikan_pengelolaan', 'surat_kuasa_pengelolaan', 'izin_usaha', 'dokumen_lainnya']);
            $table->string('document_name', 150);
            $table->string('document_number', 100)->nullable();
            $table->string('file_path', 255);
            $table->enum('status', ['menunggu_review', 'valid', 'ditolak']);
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['boarding_house_id', 'document_type', 'status'], 'bhld_bh_id_doc_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boarding_house_legal_documents');
    }
};
