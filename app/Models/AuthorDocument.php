<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthorDocument extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'manuscript_id', 
        'document_type', 
        'file_path', 
        'is_valid', 
        'uploaded_at'
    ];
}