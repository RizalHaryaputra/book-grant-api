<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManuscriptFile extends Model
{
    use HasFactory;

    protected $table = 'manuscript_files';

    public $timestamps = false;

    protected $fillable = [
        'manuscript_id',
        'file_path',
        'file_type',
        'version',
        'revision_note',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    /**
     * Get the manuscript that owns the file.
     */
    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class, 'manuscript_id');
    }
}
