<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class NotificationLog extends Model
{
    use HasUuids;

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
        'created_at'   // <-- TAMBAHKAN INI
    ];

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // <-- KARENA HANYA ADA created_at (tidak ada updated_at)
}