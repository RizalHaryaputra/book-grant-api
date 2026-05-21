<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manuscript extends Model
{
    use HasFactory;

    protected $table = 'manuscripts';

    protected $fillable = [
        'author_id',
        'proposal_id',
        'title',
        'book_type',
        'abstract',
        'science_field',
        'total_pages',
        'status',
    ];

    /**
     * Get the author of the manuscript.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the files associated with the manuscript.
     */
    public function files(): HasMany
    {
        return $this->hasMany(ManuscriptFile::class, 'manuscript_id');
    }

    /**
     * Get the review submissions for the manuscript.
     */
    public function reviewSubmissions(): HasMany
    {
        return $this->hasMany(ReviewSubmission::class, 'manuscript_id');
    }
}
