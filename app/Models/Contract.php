<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;
    public $timestamps = false;

    // 1. Daftar kolom yang DIIZINKAN untuk diisi secara massal (Mass Assignment)
    protected $fillable = [
        'author_profile_id',
        'file_url',
        'status',
        'rejection_reason',
        'uploaded_at',
        'validated_at'
    ];

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