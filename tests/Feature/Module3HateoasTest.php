<?php

use App\Models\User;
use App\Models\Manuscript;
use App\Models\ReviewSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed roles and initial data
    DB::table('roles')->insert([
        ['id' => 1, 'name' => 'admin'],
        ['id' => 2, 'name' => 'reviewer'],
        ['id' => 3, 'name' => 'author'],
        ['id' => 4, 'name' => 'editor'],
    ]);

    // Create Admin
    $this->admin = User::create([
        'id' => 1,
        'role_id' => 1,
        'name' => 'Admin User',
        'email' => 'admin@test.com',
        'password' => bcrypt('password123'),
        'is_active' => 1,
    ]);

    // Create Reviewer
    $this->reviewer = User::create([
        'id' => 2,
        'role_id' => 2,
        'name' => 'Reviewer User',
        'email' => 'reviewer@test.com',
        'password' => bcrypt('password123'),
        'is_active' => 1,
    ]);

    // Create Author
    $this->author = User::create([
        'id' => 3,
        'role_id' => 3,
        'name' => 'Author User',
        'email' => 'author@test.com',
        'password' => bcrypt('password123'),
        'is_active' => 1,
    ]);

    // Create Manuscript
    $this->manuscript = Manuscript::create([
        'id' => 1,
        'author_id' => $this->author->id,
        'title' => 'Test Manuscript Title',
        'book_type' => 'Buku Referensi',
        'status' => 'initial_draft_uploaded',
        'abstract' => 'This is a test abstract.',
        'total_pages' => 120,
        'category' => 'Technology',
    ]);

    // Seed Rubric
    DB::table('assessment_rubric')->insert([
        ['id' => 1, 'criteria' => 'Orisinalitas', 'description' => 'Tingkat keaslian naskah', 'weight' => 25, 'book_type' => 'Buku Referensi', 'status' => 1],
        ['id' => 2, 'criteria' => 'Metodologi', 'description' => 'Kesesuaian metode penelitian', 'weight' => 35, 'book_type' => 'Buku Referensi', 'status' => 1],
    ]);
});

test('reviewer dashboard contains get_details link for each task item', function () {
    // Assign reviewer to manuscript
    ReviewSubmission::create([
        'id' => 1,
        'reviewer_id' => $this->reviewer->id,
        'manuscript_id' => $this->manuscript->id,
        'status' => 'pending',
        'deadline' => now()->addDays(7),
    ]);

    $response = $this->actingAs($this->reviewer)
        ->getJson('/api/reviewer/dashboard');

    $response->assertStatus(200)
        ->assertJsonPath('data.0.links.0.rel', 'get_details')
        ->assertJsonPath('data.0.links.0.method', 'GET')
        ->assertJsonPath('data.0.links.0.href', url('/api/reviewer/manuscripts/' . $this->manuscript->id));
});

test('reviewer rubric response contains top-level links and no individual criteria links', function () {
    // Assign reviewer
    ReviewSubmission::create([
        'id' => 1,
        'reviewer_id' => $this->reviewer->id,
        'manuscript_id' => $this->manuscript->id,
        'status' => 'under_review',
        'deadline' => now()->addDays(7),
    ]);

    $response = $this->actingAs($this->reviewer)
        ->getJson("/api/reviewer/manuscripts/{$this->manuscript->id}/rubric");

    $response->assertStatus(200)
        ->assertJsonFragment([
            'links' => [
                [
                    'rel' => 'self',
                    'method' => 'GET',
                    'href' => url("/api/reviewer/manuscripts/{$this->manuscript->id}/rubric")
                ],
                [
                    'rel' => 'submit_review',
                    'method' => 'POST',
                    'href' => url("/api/reviewer/manuscripts/{$this->manuscript->id}/review")
                ],
                [
                    'rel' => 'get_details',
                    'method' => 'GET',
                    'href' => url("/api/reviewer/manuscripts/{$this->manuscript->id}")
                ]
            ]
        ]);

    // Assert that criteria objects in data contain links
    $data = $response->json('data');
    expect($data)->toBeArray()->toHaveCount(2);
    expect($data[0])->toHaveKey('links');
    expect($data[1])->toHaveKey('links');
});

test('manuscript resource contains remove_reviewer links inside reviewers for Admin', function () {
    // Assign reviewer
    ReviewSubmission::create([
        'id' => 1,
        'reviewer_id' => $this->reviewer->id,
        'manuscript_id' => $this->manuscript->id,
        'status' => 'pending',
        'deadline' => now()->addDays(7),
    ]);
    
    $this->manuscript->update(['status' => 'reviewer_assigned']);

    $response = $this->actingAs($this->admin)
        ->getJson("/api/admin/manuscripts");

    $response->assertStatus(200);
    
    $reviewersList = $response->json('data.0.reviewers');
    expect($reviewersList)->toHaveCount(1);
    expect($reviewersList[0]['links'][0])->toEqual([
        'rel' => 'remove_reviewer',
        'method' => 'DELETE',
        'href' => url("/api/admin/manuscripts/{$this->manuscript->id}/remove-reviewer/{$this->reviewer->id}")
    ]);
});

test('manuscript resource contains download_draft link for reviewers', function () {
    // Assign reviewer
    ReviewSubmission::create([
        'id' => 1,
        'reviewer_id' => $this->reviewer->id,
        'manuscript_id' => $this->manuscript->id,
        'status' => 'under_review',
        'deadline' => now()->addDays(7),
    ]);

    $this->manuscript->update(['status' => 'under_review']);

    $response = $this->actingAs($this->reviewer)
        ->getJson("/api/reviewer/manuscripts/{$this->manuscript->id}");

    $response->assertStatus(200);
    
    $links = $response->json('data.links');
    $downloadLink = collect($links)->firstWhere('rel', 'download_draft');
    
    expect($downloadLink)->not->toBeNull();
    expect($downloadLink['method'])->toBe('GET');
    expect($downloadLink['href'])->toBe(url("/api/reviewer/manuscripts/{$this->manuscript->id}/download"));
});

test('submitting review updates manuscript status to accepted when score is high', function () {
    ReviewSubmission::create([
        'reviewer_id' => $this->reviewer->id,
        'manuscript_id' => $this->manuscript->id,
        'status' => 'under_review',
        'deadline' => now()->addDays(7),
    ]);
    
    $this->manuscript->update(['status' => 'under_review']);

    $response = $this->actingAs($this->reviewer)
        ->postJson("/api/reviewer/manuscripts/{$this->manuscript->id}/review", [
            'rubric_scores' => [
                ['criteria_id' => 1, 'score' => 80],
                ['criteria_id' => 2, 'score' => 80],
            ],
            'narrative_feedback' => 'Good work.',
        ]);

    $response->assertStatus(200);
    $this->manuscript->refresh();
    expect($this->manuscript->status)->toBe('accepted');
});

test('submitting review updates manuscript status to revise when score is low', function () {
    ReviewSubmission::create([
        'reviewer_id' => $this->reviewer->id,
        'manuscript_id' => $this->manuscript->id,
        'status' => 'under_review',
        'deadline' => now()->addDays(7),
    ]);
    
    $this->manuscript->update(['status' => 'under_review']);

    $response = $this->actingAs($this->reviewer)
        ->postJson("/api/reviewer/manuscripts/{$this->manuscript->id}/review", [
            'rubric_scores' => [
                ['criteria_id' => 1, 'score' => 60],
                ['criteria_id' => 2, 'score' => 60],
            ],
            'narrative_feedback' => 'Needs improvement.',
        ]);

    $response->assertStatus(200);
    $this->manuscript->refresh();
    expect($this->manuscript->status)->toBe('revise');
});

test('admin manuscripts index response contains root-level links', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/admin/manuscripts');

    $response->assertStatus(200)
        ->assertJsonFragment([
            'links' => [
                [
                    'rel' => 'self',
                    'method' => 'GET',
                    'href' => url('/api/admin/manuscripts')
                ],
                [
                    'rel' => 'unassigned_manuscripts',
                    'method' => 'GET',
                    'href' => url('/api/admin/manuscripts/unassigned')
                ],
                [
                    'rel' => 'get_reviewers',
                    'method' => 'GET',
                    'href' => url('/api/admin/reviewers')
                ]
            ]
        ]);
});

test('admin unassigned manuscripts response contains root-level links', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/admin/manuscripts/unassigned');

    $response->assertStatus(200)
        ->assertJsonFragment([
            'links' => [
                [
                    'rel' => 'self',
                    'method' => 'GET',
                    'href' => url('/api/admin/manuscripts/unassigned')
                ],
                [
                    'rel' => 'all_manuscripts',
                    'method' => 'GET',
                    'href' => url('/api/admin/manuscripts')
                ],
                [
                    'rel' => 'get_reviewers',
                    'method' => 'GET',
                    'href' => url('/api/admin/reviewers')
                ]
            ]
        ]);
});

test('admin reviewers list response contains root-level links', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/admin/reviewers');

    $response->assertStatus(200)
        ->assertJsonFragment([
            'links' => [
                [
                    'rel' => 'self',
                    'method' => 'GET',
                    'href' => url('/api/admin/reviewers')
                ],
                [
                    'rel' => 'all_manuscripts',
                    'method' => 'GET',
                    'href' => url('/api/admin/manuscripts')
                ],
                [
                    'rel' => 'unassigned_manuscripts',
                    'method' => 'GET',
                    'href' => url('/api/admin/manuscripts/unassigned')
                ]
            ]
        ]);
});

test('compiled reviews response contains root-level links', function () {
    $response = $this->actingAs($this->admin)
        ->getJson("/api/manuscripts/{$this->manuscript->id}/compiled-reviews");

    $response->assertStatus(200)
        ->assertJsonFragment([
            'links' => [
                [
                    'rel' => 'self',
                    'method' => 'GET',
                    'href' => url("/api/manuscripts/{$this->manuscript->id}/compiled-reviews")
                ]
            ]
        ]);
});
