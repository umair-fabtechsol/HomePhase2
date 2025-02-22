<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'role' => 0,
            'phone' => '123456789',
            'terms' => 1,
            'password' => 'aszx1234',
        ]);
        User::factory()->create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'role' => 1,
            'phone' => '123456789',
            'terms' => 1,
            'password' => 'aszx1234',
        ]);

        User::factory()->create([
            'name' => 'Service Provider',
            'email' => 'provider@example.com',
            'role' => 2,
            'phone' => '123456789',
            'terms' => 1,
            'password' => 'aszx1234',
        ]);
        User::factory()->create([
            'name' => 'Sales Reps',
            'email' => 'salesreps@example.com',
            'role' => 3,
            'phone' => '123456789',
            'terms' => 1,
            'password' => 'aszx1234',
            'created_by' => 0,
            'permission_1' => '1',
            'permission_2' => '2',
            'permission_3' => '3',
            
            
            
        ]);
    }
}