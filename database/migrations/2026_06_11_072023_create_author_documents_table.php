<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('author_documents', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel manuscripts
            $table->foreignId('manuscript_id')->constrained('manuscripts')->onUpdate('cascade')->onDelete('cascade');
            
            $table->enum('document_type', ['surat_pernyataan', 'scan_bermeteri'])->nullable();
            $table->string('file_path', 255)->nullable();
            $table->boolean('is_valid')->default(0);
            
            // Uploaded at default current timestamp
            $table->timestamp('uploaded_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('author_documents');
    }
};