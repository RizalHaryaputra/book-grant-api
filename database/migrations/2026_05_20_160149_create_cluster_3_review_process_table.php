<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Review Submissions Table
        Schema::create('review_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('manuscript_id')->constrained('manuscripts')->onUpdate('cascade')->onDelete('cascade');
            $table->enum('status', ['pending', 'under_review', 'review_completed'])->nullable();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // 2. Assessment Rubric Table
        Schema::create('assessment_rubric', function (Blueprint $table) {
            $table->id();
            $table->text('criteria');
            $table->enum('book_type', ['Buku Ajar', 'Buku Referensi'])->nullable();
            $table->text('description')->nullable();
            $table->integer('weight')->nullable();
            $table->tinyInteger('status')->default(1);
        });

        // 3. Review Scores Table (Disini kolom nile sudah diubah menjadi nilai)
        Schema::create('review_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rs_id')->constrained('review_submissions')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('rubric_id')->constrained('assessment_rubric')->onUpdate('cascade')->onDelete('restrict');
            $table->integer('nilai')->nullable(); 
        });

        // 4. Review Outcomes Table
        Schema::create('review_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rs_id')->unique()->constrained('review_submissions')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('score_id')->constrained('review_scores')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('rubric_id')->constrained('assessment_rubric')->onUpdate('cascade')->onDelete('restrict');
            $table->integer('overall_score')->nullable();
            $table->boolean('status')->nullable();
            $table->timestamp('timestamp')->useCurrent();
        });

        // 5. Review Comments Table
        Schema::create('review_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rs_id')->unique()->constrained('review_submissions')->onUpdate('cascade')->onDelete('cascade');
            $table->text('comment')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_comments');
        Schema::dropIfExists('review_outcomes');
        Schema::dropIfExists('review_scores');
        Schema::dropIfExists('assessment_rubric');
        Schema::dropIfExists('review_submissions');
    }
};