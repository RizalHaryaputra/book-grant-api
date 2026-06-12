<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'author', 'reviewer', 'editor'];
        foreach ($roles as $roleName) {
            Role::create(['name' => $roleName]);
        }
    }
}