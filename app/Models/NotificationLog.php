<?php
// app/Models/NotificationLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $table = 'notification_logs';
    
    protected $fillable = [
        'template_id',
        'recipient_id',
        'manuscript_id',
        'email_to',
        'subject',
        'status',
        'sent_at',
        'error_message',
        'created_at'
    ];

    public $timestamps = false; // hanya created_at

    // Relasi ke NotificationTemplate
    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }
}