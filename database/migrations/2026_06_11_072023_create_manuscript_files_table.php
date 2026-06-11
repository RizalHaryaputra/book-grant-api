<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('manuscript_files', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel manuscripts
            $table->foreignId('manuscript_id')->constrained('manuscripts')->onUpdate('cascade')->onDelete('cascade');
            
            $table->string('file_path', 255)->nullable();
            $table->enum('file_type', ['initial', 'revision', 'draft'])->nullable();
            $table->integer('version')->default(1);
            $table->text('revision_note')->nullable();
            
            // Uploaded at default current timestamp
            $table->timestamp('uploaded_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('manuscript_files');
    }
};