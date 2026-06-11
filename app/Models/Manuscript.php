<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manuscript extends Model
{
    use HasFactory;

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
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function files()
    {
        return $this->hasMany(ManuscriptFile::class);
    }

    public function authorDocuments()
    {
        return $this->hasMany(AuthorDocument::class);
    }
}
