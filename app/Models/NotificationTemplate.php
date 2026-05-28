<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class NotificationTemplate extends Model
{
    use HasUuids;

    protected $table = 'notification_templates';

    protected $fillable = [
        'event_type',
        'subject',
        'body_html'
    ];

    protected $keyType = 'string';

    public $incrementing = false;
}