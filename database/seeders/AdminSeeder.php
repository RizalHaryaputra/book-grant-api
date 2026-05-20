<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@hibahbuku.com',
            'password' => Hash::make('rahasia123'),
            'role_id' => 1, // Pastikan RoleSeeder jalan duluan agar ID 1 (admin) tersedia
            'is_active' => true,
        ]);
    }
}