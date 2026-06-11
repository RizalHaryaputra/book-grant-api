<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Panggil Seeder Kelompok 4 (Hanya jika filenya sudah di-merge/ada)
        if (class_exists(\Database\Seeders\UserSeeder::class)) {
            $this->call(\Database\Seeders\UserSeeder::class);
        }

        // 2. Panggil Seeder Kelompok 3 (Modul 2)
        $this->call([
            ManuscriptSeeder::class,
            ManuscriptFileSeeder::class,
            AuthorDocumentSeeder::class,
        ]);
    }
}