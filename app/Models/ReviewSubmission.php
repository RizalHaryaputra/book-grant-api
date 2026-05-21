<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewSubmission extends Model
{
    use HasFactory;

    protected $table = 'review_submissions';

    protected $fillable = [
        'reviewer_id',
        'manuscript_id',
        'status',
        'deadline',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    /**
     * Get the reviewer (user) associated with the submission.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the manuscript associated with the submission.
     */
    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class, 'manuscript_id');
    }
}
