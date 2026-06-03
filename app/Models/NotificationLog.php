<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NotificationLog Model
 *
 * Immutable audit log of every notification dispatched by the system.
 * Supports One-to-Many: one user or manuscript can have many log entries.
 *
 * @property int         $id
 * @property int|null    $recipient_id
 * @property int|null    $manuscript_id
 * @property int|null    $rs_id
 * @property string      $event_type
 * @property string|null $email_to
 * @property string|null $subject
 * @property string|null $body_html
 * @property string      $status      pending|sent|failed
 * @property \Carbon\Carbon|null $sent_at
 * @property string|null $error_message
 */
class NotificationLog extends Model
{
    protected $table = 'notification_log';

    protected $fillable = [
        'recipient_id',
        'manuscript_id',
        'rs_id',
        'event_type',
        'email_to',
        'subject',
        'body_html',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    /**
     * The user who received this notification.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * The manuscript associated with this notification, if any.
     */
    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class);
    }

    // NOTE: ReviewSubmission relationship is intentionally omitted.
    // The ReviewSubmission model belongs to Modul 2/3 — referencing it
    // here would create a cross-module hard dependency. Use rs_id as
    // a plain integer FK for cross-module reference only.
}