<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthorProfile extends Model
{
    protected $table = 'authors_profile';

    // Sesuaikan persis dengan nama kolom di phpMyAdmin
    protected $fillable = [
        'user_id',
        'institutions',       
        'book_title',
        'book_type',
        'at_ethics_agreed',   
        'willingness_status', // sesuaikan DB
        'status',
        'uploaded_at'
    ];
}