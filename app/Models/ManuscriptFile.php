<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManuscriptFile extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'manuscript_id', 
        'file_path', 
        'file_type', 
        'version', 
        'revision_note', 
        'uploaded_at'
    ];
}