<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==========================================
        // 1. Publisher Checks Table (sesuai ERD)
        // ==========================================
        Schema::create('publisher_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->unique()->constrained('manuscripts')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('publisher_id')->unique()->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->boolean('cover_ok')->nullable();
            $table->boolean('page_count_ok')->nullable();
            $table->boolean('admin_docs_ok')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // ==========================================
        // 2. Deadlines Table (sesuai ERD)
        // ==========================================
        Schema::create('deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->unique()->constrained('manuscripts')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('assignee_id')->unique()->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->enum('deadline_type', ['draft_upload', 'review', 'revision', 'preprint'])->nullable();
            $table->date('due_date')->nullable();  // DATE, bukan TIMESTAMP
            $table->enum('status', ['active', 'completed', 'expired'])->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // ==========================================
        // 3. Notification Templates Table (dibuat dulu untuk FK)
        // ==========================================
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
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
            ])->unique();
            $table->string('subject', 255);
            $table->text('body_html');
            $table->timestamps();
        });

        // ==========================================
        // 4. Notification Log Table (sesuai ERD)
        // ==========================================
        Schema::create('notification_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('notification_templates')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('manuscript_id')->constrained('manuscripts')->onUpdate('cascade')->onDelete('cascade');
            $table->string('email_to', 255);
            $table->string('subject', 255);
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // Tidak ada updated_at, rs_id, event_type, body_html
        });

        // ==========================================
        // 5. Publisher Decisions Table
        // ==========================================
        Schema::create('publisher_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_id')->constrained('publisher_checks')->onDelete('cascade');
            $table->foreignId('publisher_id')->constrained('users')->onDelete('cascade');
            $table->enum('decision', ['approved', 'revised']);
            $table->text('revision_notes')->nullable();
            $table->timestamp('decided_at')->useCurrent();
            $table->timestamps();
        });

        // ==========================================
        // 6. Reminder Logs Table
        // ==========================================
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deadline_id')->constrained('deadlines')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->integer('days_before')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // ==========================================
        // TAMBAHAN INDEX KINERJA
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
            $table->index('status', 'idx_notification_log_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
        Schema::dropIfExists('publisher_decisions');
        Schema::dropIfExists('notification_log');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('deadlines');
        Schema::dropIfExists('publisher_checks');
    }
};