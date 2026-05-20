<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'penulis', 'reviewer', 'penerbit'];
        foreach ($roles as $roleName) {
            Role::create(['name' => $roleName]);
        }
    }
}