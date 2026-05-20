<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Manuscripts Table
        Schema::create('manuscripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->bigInteger('proposal_id')->nullable();
            $table->string('title', 255);
            $table->enum('book_type', ['Buku Ajar', 'Buku Referensi'])->nullable();
            $table->text('abstract')->nullable();
            $table->enum('science_field', ['Bidang Ilmu A', 'Bidang Ilmu B'])->nullable();
            $table->integer('total_pages')->nullable();
            $table->string('status', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // 2. Contracts Table
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_profile_id')->constrained('authors_profile')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('manuscript_id')->nullable()->unique()->constrained('manuscripts')->onUpdate('cascade')->onDelete('set null');
            $table->string('contract_number', 100)->nullable();
            $table->string('file_url', 255)->nullable();
            $table->enum('status', ['pending', 'active', 'expired', 'terminated'])->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('validated_at')->nullable();
        });

        // 3. Manuscript Files Table
        Schema::create('manuscript_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->onUpdate('cascade')->onDelete('cascade');
            $table->string('file_path', 255)->nullable();
            $table->enum('file_type', ['initial', 'revision', 'draft'])->nullable();
            $table->integer('version')->default(1);
            $table->text('revision_note')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
        });

        // 4. Author Documents Table
        Schema::create('author_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->onUpdate('cascade')->onDelete('cascade');
            $table->enum('document_type', ['surat_pernyataan', 'scan_bermeteri'])->nullable();
            $table->string('file_path', 255)->nullable();
            $table->tinyInteger('is_valid')->default(0);
            $table->timestamp('uploaded_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_documents');
        Schema::dropIfExists('manuscript_files');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('manuscripts');
    }
};