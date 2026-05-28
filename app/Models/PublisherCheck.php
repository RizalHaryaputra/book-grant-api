<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PublisherCheck extends Model
{
    use HasUuids;

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

    protected $keyType = 'string';

    public $incrementing = false;
}