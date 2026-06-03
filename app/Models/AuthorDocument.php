<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuthorDocument Model
 *
 * Represents an administrative document uploaded by an author
 * in support of a manuscript submission (e.g., KTP, NPWP, etc.).
 *
 * @property int         $id
 * @property int         $manuscript_id
 * @property string      $document_type
 * @property string      $file_path
 */
class AuthorDocument extends Model
{
    protected $table = 'author_documents';

    public $timestamps = false;

    protected $fillable = [
        'manuscript_id',
        'document_type',
        'file_path',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    /**
     * The manuscript this document belongs to.
     */
    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class);
    }
}
