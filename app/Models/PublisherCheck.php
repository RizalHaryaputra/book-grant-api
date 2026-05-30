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
        'decision',          // ditambahkan karena ada di migration
        'notes',
        'checked_at'
    ];

    // Tidak ada relasi ke PublisherDecision (tabel dihapus)
}