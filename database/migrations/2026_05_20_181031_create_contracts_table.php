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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_profile_id')->constrained('authors_profile')->onUpdate('cascade')->onDelete('restrict');
            
            // Relasi ke tabel kelompok 3 (Naskah)
            $table->unsignedBigInteger('manuscript_id')->nullable()->unique();
            
            $table->string('contract_number', 100)->nullable();
            $table->string('file_url', 255)->nullable();
            
            // Status disesuaikan dengan API YAML Kontrak
            $table->enum('status', ['uploaded', 'validated', 'rejected', 'revision'])->default('uploaded');
            $table->text('rejection_reason')->nullable();
            
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
