<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema; // Tambahan Wajib
use Carbon\Carbon;

class ManuscriptSeeder extends Seeder
{
    public function run()
    {
        $authorId = null;

        // CEK KEAMANAN LEVEL 1: Apakah tabel roles buatan K-4 sudah ada?
        if (Schema::hasTable('roles') && Schema::hasColumn('users', 'role_id')) {
            
            // SKENARIO A: K-4 sudah siap, struktur database mereka sudah ada.
            $author = DB::table('users')
                ->join('roles', 'users.role_id', '=', 'roles.id')
                ->where('roles.name', 'author')
                ->select('users.id')
                ->first();

            if ($author) {
                $authorId = $author->id;
            } else {
                $role = DB::table('roles')->where('name', 'author')->first();
                $roleId = $role ? $role->id : DB::table('roles')->insertGetId(['name' => 'author']);

                $authorId = DB::table('users')->insertGetId([
                    'role_id' => $roleId,
                    'name' => 'Penulis Darurat (Otomatis K-3)',
                    'email' => 'penulis_dummy@example.com',
                    'password' => Hash::make('password123'),
                    'is_active' => 1,
                    'created_at' => Carbon::now(),
                ]);
            }
        } else {
            // SKENARIO B: K-4 BELUM siap (Tabel roles tidak ada).
            // Pakai tabel users bawaan asli Laravel agar proses tim K-3 tidak terhambat.
            $user = DB::table('users')->first();
            
            if ($user) {
                $authorId = $user->id;
            } else {
                $authorId = DB::table('users')->insertGetId([
                    'name' => 'Penulis Darurat (Mode Default)',
                    'email' => 'penulis_dummy@example.com',
                    'password' => Hash::make('password123'),
                    'created_at' => Carbon::now(),
                ]);
            }
        }

        // EKSEKUSI INSERT NASKAH KELOMPOK 3 (Sekarang 100% Aman)
        DB::table('manuscripts')->insert([
            'author_id' => $authorId, 
            'proposal_id' => null,
            'title' => 'Pengantar Teknologi Informasi untuk Pemula',
            'book_type' => 'Buku Ajar',
            'abstract' => 'Buku ini membahas konsep dasar sistem komputer, jaringan, dan pengembangan perangkat lunak.',
            'science_field' => 'Bidang Ilmu A',
            'total_pages' => 120,
            'status' => 'revision_required', 
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}