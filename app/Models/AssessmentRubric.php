<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentRubric extends Model
{
    public $timestamps = false; // Tabel tidak punya created_at/updated_at

    protected $table = 'assessment_rubric';

    protected $fillable = [
        'criteria',
        'book_type',
        'description',
        'weight',
        'status',
    ];

    protected $casts = [
        'weight' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Cek apakah total bobot untuk suatu book_type masih <= 100
     * @param string $bookType 'Buku Ajar' atau 'Buku Referensi'
     * @param int|null $exceptId abaikan ID tertentu (untuk update)
     */
    public static function isTotalWeightValid(string $bookType, ?int $exceptId = null): bool
    {
        $query = self::where('book_type', $bookType)
            ->where('status', 1); // hanya hitung rubrik aktif

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $total = $query->sum('weight');

        return $total <= 100;
    }

    /**
     * Ambil total bobot saat ini untuk book_type
     */
    public static function getCurrentTotalWeight(string $bookType, ?int $exceptId = null): int
    {
        $query = self::where('book_type', $bookType)
            ->where('status', 1);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->sum('weight');
    }
}