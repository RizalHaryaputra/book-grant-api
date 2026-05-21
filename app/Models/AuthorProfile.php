<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthorProfile extends Model
{
    protected $table = 'author_profiles'; 

    protected $fillable = [
        'user_id',
        'institution', 
        'book_title',
        'book_type',
        'ai_ethics_agreed',   
        'willingness_statement', 
        'status'
    ];
}