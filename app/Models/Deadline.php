<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Deadline extends Model
{
    use HasUuids;

    protected $table = 'deadlines';

    protected $fillable = [
        'manuscript_id',
        'assignee_id',
        'deadline_type',
        'due_date',
        'status'
    ];

    protected $keyType = 'string';

    public $incrementing = false;
}