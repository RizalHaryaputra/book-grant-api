<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ManuscriptIntegrationService
{
    protected $baseUrl;

    public function __construct()
    {
        // Ambil URL API Kelompok 2 dari file .env
        $this->baseUrl = config('services.kelompok2.api_url', env('KELOMPOK2_API_URL'));
    }

    /**
     * Update status manuskrip di sistem Kelompok 2
     */
    public function updateManuscriptStatus(int $manuscriptId, string $status, ?string $notes = null): bool
    {
        $url = "{$this->baseUrl}/api/manuscripts/{$manuscriptId}/status";

        $payload = [
            'status' => $status,
            'revision_notes' => $notes,
        ];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.kelompok2.api_token'),
            ])->patch($url, $payload);

            if ($response->successful()) {
                Log::info("Status manuskrip {$manuscriptId} berhasil diupdate menjadi {$status}.");
                return true;
            } else {
                Log::error("Gagal update status manuskrip {$manuscriptId}. Response: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Exception saat update status manuskrip {$manuscriptId}: " . $e->getMessage());
            return false;
        }
    }
}