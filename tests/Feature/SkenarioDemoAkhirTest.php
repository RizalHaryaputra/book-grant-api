<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Manuscript;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SkenarioDemoAkhirTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup Roles
        Role::insert([
            ['name' => 'admin'],
            ['name' => 'author'],
            ['name' => 'reviewer'],
            ['name' => 'editor'],
        ]);
        
        // Setup basic user admin & reviewer & editor
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('name', 'admin')->first()->id,
            'is_active' => 1
        ]);
        
        User::create([
            'name' => 'Reviewer User',
            'email' => 'reviewer@test.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('name', 'reviewer')->first()->id,
            'is_active' => 1
        ]);
        
        User::create([
            'name' => 'Editor User',
            'email' => 'editor@test.com',
            'password' => bcrypt('password'),
            'role_id' => Role::where('name', 'editor')->first()->id,
            'is_active' => 1
        ]);

        \Illuminate\Support\Facades\DB::table('assessment_rubric')->insert([
            ['id' => 1, 'criteria' => 'Kelayakan Isi', 'weight' => 50, 'status' => 1],
        ]);

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_full_scenario()
    {
        // 1 & 2. Penulis mengisi form kesediaan -> Sistem membuat akun
        $response = $this->postJson('/api/author-confirmations', [
            'name' => 'Penulis Baru',
            'email' => 'penulis@test.com',
            'institution' => 'Kampus Uji',
            'book_title' => 'Buku Testing Terpadu',
            'book_type' => 'Buku Ajar',
            'ai_ethics_agreed' => true,
            'willingness_statement' => true,
            'co_authors' => [
                ['name' => 'Co-author 1']
            ]
        ]);
        
        $response->dump();
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'penulis@test.com'
        ]);

        $authorUser = User::where('email', 'penulis@test.com')->first();
        $authorUser->update(['password' => bcrypt('password123')]);

        // 3. Penulis login
        $response = $this->postJson('/api/auth/login', [
            'email' => 'penulis@test.com',
            'password' => 'password123' 
        ]);
        $response->assertStatus(200);
        $token = $response->json('data.token') ?? $response->json('token');
        
        // Cek My Contract (Akan 404 jika belum di-generate/upload)
        $respContract = $this->actingAs($authorUser, 'sanctum')->getJson('/api/author/contracts/my-contract');
        $respContract->assertStatus(404);

        // 4. Penulis mengunggah kontrak yang telah ditandatangani
        $file = UploadedFile::fake()->create('kontrak_ttd.pdf', 100);
        $response = $this->actingAs($authorUser, 'sanctum')->postJson('/api/author/contracts/upload', [
            'contract_file' => $file
        ]);
        $response->dump();
        $response->assertStatus(201);

        $contractId = $response->json('data.id') ?? \App\Models\Contract::first()->id;

        // 5. Admin memvalidasi kontrak
        $admin = User::where('email', 'admin@test.com')->first();
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/contracts/' . $contractId . '/validate', [
            'status' => 'active'
        ]);
        $response->dump();
        $response->assertStatus(200);

        // 6. Penulis mengunggah draft awal
        $draftFile = UploadedFile::fake()->create('draft_awal.pdf', 1000);
        $response = $this->actingAs($authorUser, 'sanctum')->postJson('/api/author/manuscripts/drafts', [
            'title' => 'Buku Testing Terpadu Final',
            'abstract' => 'Abstrak Buku',
            'science_field' => 'Bidang Ilmu A',
            'total_pages' => 100,
            'book_type' => 'Buku Ajar',
            'file_draft' => $draftFile
        ]);
        $response->assertStatus(201);
        $manuscriptId = Manuscript::first()->id;

        // 7. Admin memplot reviewer
        $reviewer = User::where('email', 'reviewer@test.com')->first();
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/manuscripts/' . $manuscriptId . '/assign-reviewer', [
            'reviewer_id' => $reviewer->id,
            'deadline' => now()->addDays(7)->toDateString()
        ]);
        $response->assertStatus(200);

        // 8. Reviewer memberi nilai dan catatan
        $response = $this->actingAs($reviewer, 'sanctum')->postJson('/api/reviewer/manuscripts/' . $manuscriptId . '/review', [
            'narrative_feedback' => 'Tolong perbaiki bab 1',
            'rubric_scores' => [
                ['criteria_id' => 1, 'score' => 70]
            ],
            'decision' => 'revision_required'
        ]);
        $response->dump();
        $response->assertStatus(200);

        // 9. Penulis melihat review dan mengunggah revisi
        $respReviews = $this->actingAs($authorUser, 'sanctum')->getJson('/api/author/manuscripts/' . $manuscriptId . '/reviews');
        $respReviews->dump();
        $respReviews->assertStatus(200);
            
        $revisionFile = UploadedFile::fake()->create('revisi.pdf', 1000);
        $response = $this->actingAs($authorUser, 'sanctum')->postJson('/api/author/manuscripts/' . $manuscriptId . '/revisions', [
            'file_revision' => $revisionFile,
            'revision_note' => 'Sudah diperbaiki bab 1'
        ]);
        $response->dump();
        $response->assertStatus(201);

        // 10. Naskah masuk pra-cetak (Penulis bisa jadi submit ke penerbit atau otomatis?)
        // Let's assume it automatically goes to pre-print or author needs to submit preprint revision
        $preprintFile = UploadedFile::fake()->create('preprint.pdf', 1000);
        $this->actingAs($authorUser, 'sanctum')->postJson('/api/author/manuscripts/' . $manuscriptId . '/preprint-revision', [
            'revision_file' => $preprintFile
        ])->assertStatus(201);

        // 11. Penerbit memberi keputusan approved atau revised
        $editor = User::where('email', 'editor@test.com')->first();
        
        // Penerbit memeriksa naskah terlebih dahulu
        $this->actingAs($editor, 'sanctum')->postJson('/api/v1/publisher/check/' . $manuscriptId, [
            'is_cover_valid' => true,
            'is_page_count_valid' => true,
            'is_admin_docs_complete' => true,
            'check_notes' => 'Semua lengkap'
        ])->assertStatus(200);

        $response = $this->actingAs($editor, 'sanctum')->postJson('/api/v1/publisher/decision', [
            'manuscript_id' => $manuscriptId,
            'status' => 'approved',
            'final_notes' => 'Siap cetak'
        ]);
        $response->assertStatus(200);

        // 12. Dashboard admin
        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/dashboard/summary')
            ->assertStatus(200);
    }
}
