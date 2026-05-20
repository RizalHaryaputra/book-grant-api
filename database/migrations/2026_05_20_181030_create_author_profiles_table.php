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
        Schema::create('authors_profile', function (Blueprint $table) {
            $table->id();
            // Nullable karena proses auto-generate akun terjadi bersamaan
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('institution', 150)->nullable();
            $table->string('book_title', 255)->nullable();
            $table->enum('book_type', ['buku ajar', 'buku referensi'])->nullable();
            $table->boolean('ai_ethics_agreed')->default(false);
            $table->boolean('willingness_statement')->default(false);
            
            // Status disesuaikan dengan API YAML Modul 1
            $table->enum('status', [
                'registered', 
                'account_created', 
                'contract_uploaded', 
                'contract_validated', 
                'contract_rejected'
            ])->default('registered');
            
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('author_profiles');
    }
};
