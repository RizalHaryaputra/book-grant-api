<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manuscript extends Model
{
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
}