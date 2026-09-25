<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $labelManagerRole = Role::where('slug', 'label-manager')->first();
        $artistManagerRole = Role::where('slug', 'artist-manager')->first();

        User::updateOrCreate(
            ['email' => 'superadmin@kayfactory.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role_id' => $superAdminRole?->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@kayfactory.test'],
            [
                'name' => 'Label Manager',
                'password' => Hash::make('password'),
                'role_id' => $labelManagerRole?->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'artistmanager@kayfactory.test'],
            [
                'name' => 'Artist Manager',
                'password' => Hash::make('password'),
                'role_id' => $artistManagerRole?->id,
            ]
        );

        $this->call(ArtistSeeder::class);
        $this->call(ContractSeeder::class);
        $this->call(TrackSeeder::class);
        $this->call(ReleaseSeeder::class);
        $this->call(DistributionSeeder::class);
        $this->call(RevenueEntrySeeder::class);
        $this->call(ExpenseSeeder::class);
        $this->call(RoyaltyStatementSeeder::class);
    }
}