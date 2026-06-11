<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ManuscriptFile Model
 *
 * Represents a file attachment belonging to a manuscript
 * (e.g., draft PDF, revised PDF, cover image).
 *
 * @property int         $id
 * @property int         $manuscript_id
 * @property string      $file_type   e.g. 'initial', 'revision', 'cover'
 * @property string      $file_path
 * @property int         $version
 * @property string      $revision_note
 * @property string      $uploaded_at
 */
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
        'uploaded_at'
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    /**
     * The manuscript this file belongs to.
     */
    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class);
    }
}
