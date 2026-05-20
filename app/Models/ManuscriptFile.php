<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManuscriptFile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'manuscript_id', 
        'file_path', 
        'file_type', 
        'version', 
        'revision_note'
    ];
}