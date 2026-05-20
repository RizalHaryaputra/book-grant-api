<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // PENTING: Matikan foreign key check sementara agar tidak error saat truncate/delete data lama
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Bersihkan data lama jika ada
        DB::table('roles')->truncate();
        DB::table('users')->truncate();
        DB::table('authors_profile')->truncate();
        DB::table('co_authors')->truncate();
        DB::table('manuscripts')->truncate();
        DB::table('assessment_rubric')->truncate();
        DB::table('review_submissions')->truncate();
        DB::table('review_scores')->truncate();

        // 1. SEED TABLE: ROLES (Data Master)
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin'],
            ['id' => 2, 'name' => 'reviewer'],
            ['id' => 3, 'name' => 'author'],
            ['id' => 4, 'name' => 'editor'],
        ]);

        // 2. SEED TABLE: USERS (Akun Contoh)
        DB::table('users')->insert([
            [
                'id' => 1,
                'role_id' => 1, // Admin
                'name' => 'Administrator Hibah',
                'email' => 'admin@hibah.com',
                'password' => Hash::make('password123'),
                'is_active' => 1,
                'created_at' => now(),
            ],
            [
                'id' => 2,
                'role_id' => 2, // Reviewer
                'name' => 'Prof. Dr. Budi Utomo',
                'email' => 'budi.reviewer@hibah.com',
                'password' => Hash::make('password123'),
                'is_active' => 1,
                'created_at' => now(),
            ],
            [
                'id' => 3,
                'role_id' => 3, // Author
                'name' => 'Dr. Andi Wijaya',
                'email' => 'andi.author@hibah.com',
                'password' => Hash::make('password123'),
                'is_active' => 1,
                'created_at' => now(),
            ],
            [
                'id' => 4,
                'role_id' => 4, // Editor
                'name' => 'Siti Aminah, M.Hum',
                'email' => 'siti.editor@hibah.com',
                'password' => Hash::make('password123'),
                'is_active' => 1,
                'created_at' => now(),
            ]
        ]);

        // 3. SEED TABLE: AUTHORS_PROFILE (Terhubung ke user_id 3)
        DB::table('authors_profile')->insert([
            'id' => 1,
            'user_id' => 3,
            'institutions' => 'Universitas Teknologi Indonesia',
            'book_title' => 'Pemrograman Web Modern dengan Laravel',
            'book_type' => 'Buku Ajar',
            'at_ethics_agreed' => 1,
            'status' => 'active',
            'created_at' => now(),
            'uploaded_at' => now(),
            'willingness_status' => 1,
        ]);

        // 4. SEED TABLE: CO_AUTHORS
        DB::table('co_authors')->insert([
            [
                'id' => 1,
                'author_profile_id' => 1,
                'name' => 'Rian Hidayat, M.T',
                'is_mandatory' => 1,
                'sort_order' => 1,
                'created_at' => now(),
            ]
        ]);

        // 5. SEED TABLE: MANUSCRIPTS
        DB::table('manuscripts')->insert([
            'id' => 1,
            'author_id' => 3, // User Andi
            'proposal_id' => 100234,
            'title' => 'Pemrograman Web Modern dengan Laravel',
            'book_type' => 'Buku Ajar',
            'abstract' => 'Buku ini membahas mengenai pengembangan API dan aplikasi web menggunakan framework Laravel versi terbaru...',
            'science_field' => 'Bidang Ilmu A',
            'total_pages' => 180,
            'status' => 'under_review',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6. SEED TABLE: ASSESSMENT_RUBRIC (Data Master Rubrik)
        DB::table('assessment_rubric')->insert([
            [
                'id' => 1,
                'criteria' => 'Kesesuaian dengan Kurikulum',
                'book_type' => 'Buku Ajar',
                'description' => 'Menilai apakah isi buku materi sudah sesuai dengan RPS perkuliahan.',
                'weight' => 30,
                'status' => 1
            ],
            [
                'id' => 2,
                'criteria' => 'Kedalaman Materi Kebahasaan',
                'book_type' => 'Buku Ajar',
                'description' => 'Menilai tata bahasa, keterbacaan, dan struktur penulisan.',
                'weight' => 40,
                'status' => 1
            ],
            [
                'id' => 3,
                'criteria' => 'Orisinalitas dan Plagiasi',
                'book_type' => 'Buku Ajar',
                'description' => 'Memastikan karya bebas dari indikasi plagiarisme berat.',
                'weight' => 30,
                'status' => 1
            ],
        ]);

        // 7. SEED TABLE: REVIEW_SUBMISSIONS (Penugasan Reviewer)
        DB::table('review_submissions')->insert([
            'id' => 1,
            'reviewer_id' => 2, // Prof. Budi
            'manuscript_id' => 1, // Buku Laravel
            'status' => 'review_completed',
            'deadline' => Carbon::now()->addDays(14),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 8. SEED TABLE: REVIEW_SCORES (Sudah menggunakan kolom 'nilai')
        DB::table('review_scores')->insert([
            [
                'id' => 1,
                'rs_id' => 1,
                'rubric_id' => 1,
                'nilai' => 85, // Kriteria 1
            ],
            [
                'id' => 2,
                'rs_id' => 1,
                'rubric_id' => 2,
                'nilai' => 90, // Kriteria 2
            ],
        ]);

        // Aktifkan kembali foreign key check
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}