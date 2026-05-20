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
        Schema::create('notification_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();

    $table->uuid('template_id')->nullable();
    $table->uuid('recipient_id')->nullable();
    $table->uuid('manuscript_id')->nullable();

    $table->string('email_to');
    $table->string('subject');

    $table->enum('status', [
        'pending',
        'sent',
        'failed'
    ])->default('pending');

    $table->timestamps();

    $table->text('error_message')->nullable();

    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};