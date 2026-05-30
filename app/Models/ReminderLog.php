<?php
// app/Models/ReminderLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReminderLog extends Model
{
    protected $table = 'reminder_logs';
    
    protected $fillable = [
        'deadline_id',
        'recipient_id',
        'days_before',
        'sent_at',
        'success',
        'error_message',
        'created_at'
    ];

    public $timestamps = false; // karena tabel hanya punya created_at, tidak ada updated_at
}