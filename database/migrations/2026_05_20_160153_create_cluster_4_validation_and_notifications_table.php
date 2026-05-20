<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==========================================
        // 1. Publisher Checks Table
        // ==========================================
        Schema::create('publisher_checks', function (Blueprint $table) {
            $table->id(); // auto-increment bigInteger
            $table->foreignId('manuscript_id')->unique()->constrained('manuscripts')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('publisher_id')->unique()->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->boolean('cover_ok')->nullable();
            $table->boolean('page_count_ok')->nullable();
            $table->boolean('admin_docs_ok')->nullable();
            $table->enum('decision', ['approved', 'revised'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // ==========================================
        // 2. Deadlines Table
        // ==========================================
        Schema::create('deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->unique()->constrained('manuscripts')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('assignee_id')->unique()->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->enum('deadline_type', ['draft_upload', 'review', 'revision', 'preprint'])->nullable();
            $table->timestamp('due_date')->nullable();
            $table->enum('status', ['active', 'completed', 'expired'])->nullable();
            $table->integer('days_before')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // ==========================================
        // 3. Notification Log Table
        // ==========================================
        Schema::create('notification_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')->unique()->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('manuscript_id')->unique()->constrained('manuscripts')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('rs_id')->unique()->constrained('review_submissions')->onUpdate('cascade')->onDelete('cascade');
            $table->enum('event_type', [
                'account_created', 'contract_validated', 'draft_uploaded',
                'review_assigned', 'review_completed', 'revision_requested',
                'preprint_entered', 'publisher_approved', 'publisher_revised',
                'deadline_reminder'
            ]);
            $table->string('email_to', 255)->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('body_html')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // ==========================================
        // 4. Publisher Decisions Table (tambahan)
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
        // 5. Reminder Logs Table (tambahan)
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
        // 6. Notification Templates Table (tambahan)
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
            $table->index('event_type', 'idx_notification_log_event');
        });
    }

    public function down(): void
    {
        // Urutan drop tabel harus terbalik dari create karena foreign key
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('reminder_logs');
        Schema::dropIfExists('publisher_decisions');
        Schema::dropIfExists('notification_log');
        Schema::dropIfExists('deadlines');
        Schema::dropIfExists('publisher_checks');
    }
};