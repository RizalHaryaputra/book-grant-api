<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthorProfile extends Model
{
    protected $table = 'authors_profile';
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'institutions', 
        'book_title',
        'book_type',
        'at_ethics_agreed',   
        'willingness_status', 
        'status'
    ];

// Relasi balik ke tabel users (AuthorProfile ini milik User siapa?)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}