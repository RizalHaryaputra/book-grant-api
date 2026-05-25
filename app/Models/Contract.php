<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    // 1. Membuka gembok agar fitur "updateOrCreate" di Controller tadi diizinkan masuk
    protected $guarded = [];

    // 2. Memberi tahu Laravel bahwa kolom ini adalah format Tanggal/Waktu (Carbon)
    protected $casts = [
        'uploaded_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    // 3. Relasi ke Profil Penulis (Kontrak ini milik siapa?)
    public function authorProfile()
    {
        return $this->belongsTo(AuthorProfile::class, 'author_profile_id', 'id');
    }
}