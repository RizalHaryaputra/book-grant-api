<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('manuscripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            
            $table->bigInteger('proposal_id')->nullable();
            $table->string('title', 255);
            $table->enum('book_type', ['Buku Ajar', 'Buku Referensi'])->nullable();
            $table->text('abstract')->nullable();
            
            $table->enum('science_field', [
                'Ilmu Komputer', 
                'Teknik Informatika', 
                'Matematika', 
                'Fisika', 
                'Bahasa & Sastra', 
                'Ekonomi', 
                'Hukum', 
                'Kedokteran', 
                'Pendidikan', 
                'Sosial & Politik'
            ])->nullable();

            $table->integer('total_pages')->nullable();
            $table->string('status', 50)->nullable();
            
            $table->timestamps(); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('manuscripts');
    }
};