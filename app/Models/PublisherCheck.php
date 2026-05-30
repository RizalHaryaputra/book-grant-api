<?php
// app/Models/PublisherCheck.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublisherCheck extends Model
{
    protected $table = 'publisher_checks';
    
    protected $fillable = [
        'manuscript_id',
        'publisher_id',
        'cover_ok',
        'page_count_ok',
        'admin_docs_ok',
        'notes',
        'checked_at'
    ];

    // Relasi ke PublisherDecision (satu check punya satu decision)
    public function decision()
    {
        return $this->hasOne(PublisherDecision::class, 'check_id');
    }
}