<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReminderLog extends Model
{
    use HasUuids;

    protected $table = 'reminder_logs';
    
    protected $fillable = [
        'deadline_id',
        'recipient_id',
        'days_before',
        'sent_at',
        'success',
        'error_message',
        'created_at'   // TAMBAHKAN INI
    ];

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // KARENA TABEL HANYA PUNYA created_at (tanpa updated_at)
}