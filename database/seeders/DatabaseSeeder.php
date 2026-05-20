<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Hanya memanggil seeder buatan kita, tanpa factory bawaan Laravel
        $this->call([
            RoleSeeder::class,
            AdminSeeder::class,
        ]);
    }
}