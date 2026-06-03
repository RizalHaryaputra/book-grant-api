<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==========================================
        // 1. Publisher Checks Table (sesuai SQL)
        // ==========================================
        Schema::create('publisher_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->unique()->constrained('manuscripts')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('publisher_id')->unique()->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->boolean('cover_ok')->nullable();
            $table->boolean('page_count_ok')->nullable();
            $table->boolean('admin_docs_ok')->nullable();
            $table->enum('decision', ['approved', 'revised'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps(); // created_at & updated_at
        });

        // ==========================================
        // 2. Deadlines Table (sesuai SQL)
        // ==========================================
        Schema::create('deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->unique()->constrained('manuscripts')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('assignee_id')->unique()->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->enum('deadline_type', ['draft_upload', 'review', 'revision', 'preprint'])->nullable();
            $table->timestamp('due_date')->nullable(); // TIMESTAMP, sesuai SQL
            $table->enum('status', ['active', 'completed', 'expired'])->nullable();
            $table->integer('days_before')->nullable();
            $table->timestamps();
        });

        // ==========================================
        // 3. Notification Log Table (sesuai SQL)
        // ==========================================
        Schema::create('notification_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('manuscript_id')->nullable()->constrained('manuscripts')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('rs_id')->nullable()->constrained('review_submissions')->onUpdate('cascade')->onDelete('cascade');
            $table->enum('event_type', [
                'account_created',
                'contract_validated',
                'draft_uploaded',
                'review_assigned',
                'review_completed',
                'revision_requested',
                'preprint_entered',
                'publisher_approved',
                'publisher_revised',
                'deadline_reminder'
            ]);
            $table->string('email_to', 255)->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('body_html')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps(); // created_at & updated_at
        });

        // ==========================================
        // TAMBAHAN INDEX KINERJA (sesuai SQL)
        // ==========================================
        Schema::table('manuscripts', function (Blueprint $table) {
            $table->index('author_id', 'idx_manuscripts_author');
        });

        Schema::table('review_submissions', function (Blueprint $table) {
            $table->index('reviewer_id', 'idx_review_submissions_rev');
            $table->index('manuscript_id', 'idx_review_submissions_ms');
        });

        Schema::table('manuscript_files', function (Blueprint $table) {
            $table->index('manuscript_id', 'idx_manuscript_files_ms');
        });

        Schema::table('author_documents', function (Blueprint $table) {
            $table->index('manuscript_id', 'idx_author_documents_ms');
        });

        Schema::table('co_authors', function (Blueprint $table) {
            $table->index('author_profile_id', 'idx_co_authors_profile');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->index('author_profile_id', 'idx_contracts_author_profile');
        });

        Schema::table('notification_log', function (Blueprint $table) {
            $table->index('event_type', 'idx_notification_log_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_log');
        Schema::dropIfExists('deadlines');
        Schema::dropIfExists('publisher_checks');
    }
};