<?php

namespace App\Models;

// 1. Tambahkan baris ini di bagian atas
use Laravel\Sanctum\HasApiTokens; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    // 2. Tambahkan HasApiTokens di dalam use class ini
    use HasApiTokens, HasFactory, Notifiable; 

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relasi ke tabel roles (Belongs-To)
   // Relasi ke tabel roles
    public function role()
    {
        return $this->belongsTo(\App\Models\Role::class, 'role_id', 'id');
    }

    // Relasi ke tabel author_profiles
    public function authorProfile()
    {
        return $this->hasOne(\App\Models\AuthorProfile::class, 'user_id', 'id');
    }
}