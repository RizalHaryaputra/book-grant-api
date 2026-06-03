<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PublisherCheck Model
 *
 * Records the publisher's physical/administrative check
 * results for a manuscript before it enters print.
 *
 * @property int         $id
 * @property int         $manuscript_id
 * @property int         $publisher_id
 * @property bool|null   $cover_ok
 * @property bool|null   $page_count_ok
 * @property bool|null   $admin_docs_ok
 * @property string|null $decision    approved|revised
 * @property string|null $notes
 * @property \Carbon\Carbon|null $checked_at
 */
class PublisherCheck extends Model
{
    protected $table = 'publisher_checks';

    protected $fillable = [
        'manuscript_id',
        'publisher_id',
        'cover_ok',
        'page_count_ok',
        'admin_docs_ok',
        'decision',
        'notes',
        'checked_at',
    ];

    protected $casts = [
        'cover_ok'      => 'boolean',
        'page_count_ok' => 'boolean',
        'admin_docs_ok' => 'boolean',
        'checked_at'    => 'datetime',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    /**
     * The manuscript being checked.
     */
    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class);
    }

    /**
     * The publisher (user) who performed the check.
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }
}