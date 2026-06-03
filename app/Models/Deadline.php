<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Deadline Model
 *
 * Tracks submission and review deadlines assigned to a user
 * for a specific manuscript stage.
 *
 * @property int         $id
 * @property int         $manuscript_id
 * @property int         $assignee_id
 * @property string      $deadline_type  draft_upload|review|revision|preprint
 * @property \Carbon\Carbon $due_date
 * @property string      $status         active|completed|expired
 * @property int|null    $days_before
 */
class Deadline extends Model
{
    protected $table = 'deadlines';

    protected $fillable = [
        'manuscript_id',
        'assignee_id',
        'deadline_type',
        'due_date',
        'status',
        'days_before',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    /**
     * The user assigned to this deadline.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * The manuscript this deadline belongs to.
     */
    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class);
    }
}