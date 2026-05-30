<?php
// app/Models/Deadline.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deadline extends Model
{
    protected $table = 'deadlines';
    
    protected $fillable = [
        'manuscript_id',
        'assignee_id',
        'deadline_type',
        'due_date',
        'status',
        'days_before'        // ditambahkan karena ada di migration
    ];

    protected $casts = [
        'due_date' => 'datetime', // karena timestamp
    ];
}