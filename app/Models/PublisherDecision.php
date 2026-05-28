<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PublisherDecision extends Model
{
    use HasUuids;

    protected $table = 'publisher_decisions';

    protected $fillable = [
        'check_id',
        'publisher_id',
        'decision',
        'revision_notes',
        'decided_at'
    ];

    protected $keyType = 'string';

    public $incrementing = false;
}