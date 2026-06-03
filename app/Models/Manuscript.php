<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manuscript extends Model
{
    protected $guarded = [];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function files()
    {
        return $this->hasMany(ManuscriptFile::class);
    }

    public function authorDocuments()
    {
        return $this->hasMany(AuthorDocument::class);
    }
}
