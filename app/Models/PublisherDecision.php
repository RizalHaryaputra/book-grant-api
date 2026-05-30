<?php
// app/Models/PublisherDecision.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublisherDecision extends Model
{
    protected $table = 'publisher_decisions';
    
    protected $fillable = [
        'check_id',
        'publisher_id',
        'decision',
        'revision_notes',
        'decided_at'
    ];

    // Relasi balik ke PublisherCheck
    public function check()
    {
        return $this->belongsTo(PublisherCheck::class, 'check_id');
    }
}