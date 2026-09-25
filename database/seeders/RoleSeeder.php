<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin'],
            ['name' => 'Label Manager', 'slug' => 'label-manager'],
            ['name' => 'A&R', 'slug' => 'ar'],
            ['name' => 'Artist Manager', 'slug' => 'artist-manager'],
            ['name' => 'Finance Staff', 'slug' => 'finance-staff'],
            ['name' => 'Marketing Staff', 'slug' => 'marketing-staff'],
            ['name' => 'Distribution Manager', 'slug' => 'distribution-manager'],
            ['name' => 'General Staff', 'slug' => 'general-staff'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}