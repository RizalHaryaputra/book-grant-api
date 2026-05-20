<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Roles Table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->enum('name', ['admin', 'reviewer', 'author', 'editor']);
        });

        // 2. Users Table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onUpdate('cascade')->onDelete('restrict');
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamp('created_at')->useCurrent();
        });

        // 3. Authors Profile Table
        Schema::create('authors_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('institutions', 150)->nullable();
            $table->string('book_title', 255)->nullable();
            $table->enum('book_type', ['Buku Ajar', 'Buku Referensi'])->nullable();
            $table->tinyInteger('at_ethics_agreed')->default(0);
            $table->enum('status', ['active', 'inactive', 'suspended'])->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('uploaded_at')->nullable();
            $table->tinyInteger('willingness_status')->default(0);
        });

        // 4. Co-Authors Table
        Schema::create('co_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_profile_id')->constrained('authors_profile')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name', 100);
            $table->tinyInteger('is_mandatory')->default(0);
            $table->integer('sort_order')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('co_authors');
        Schema::dropIfExists('authors_profile');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};