<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Sanctum\HasApiTokens; 
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
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
            'password'          => 'hashed',
        ];
    }

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

    // -----------------------------------------------------------------
    // Relationships (Tambahan Kelompok 1)
    // -----------------------------------------------------------------

    /**
     * Manuscripts authored by this user.
     */
    public function manuscripts(): HasMany
    {
        return $this->hasMany(Manuscript::class, 'author_id');
    }

    /**
     * Deadlines assigned to this user.
     */
    public function deadlines(): HasMany
    {
        return $this->hasMany(Deadline::class, 'assignee_id');
    }

    /**
     * Notification log entries where this user is the recipient.
     */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'recipient_id');
    }
}