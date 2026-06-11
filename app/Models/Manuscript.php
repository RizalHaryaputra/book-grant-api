<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
>>>>>>> origin/module-3

class Manuscript extends Model
{
    use HasFactory;

<<<<<<< HEAD
    protected $fillable = [
        'author_id', 
        'proposal_id', 
        'title', 
        'book_type', 
        'abstract', 
        'science_field', 
        'total_pages', 
        'status'
    ];

    public function author()
=======
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
>>>>>>> origin/module-3
    {
        return $this->belongsTo(User::class, 'author_id');
    }

<<<<<<< HEAD
    public function files()
    {
        return $this->hasMany(ManuscriptFile::class);
    }

    public function authorDocuments()
    {
        return $this->hasMany(AuthorDocument::class);
=======
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
>>>>>>> origin/module-3
    }
}
