<?php
// app/Models/NotificationLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $table = 'notification_log';
    
    protected $fillable = [
        'recipient_id',
        'manuscript_id',
        'rs_id',             // foreign key ke review_submissions
        'event_type',
        'email_to',
        'subject',
        'body_html',
        'status',
        'sent_at',
        'error_message'
    ];

    // Gunakan timestamps default Laravel (created_at & updated_at)
    // Tidak perlu set $timestamps = false, karena migration punya kedua kolom

    // Relasi ke review_submissions (jika diperlukan)
    public function reviewSubmission()
    {
        return $this->belongsTo(ReviewSubmission::class, 'rs_id');
    }
}