<?php

namespace Database\Seeders;

use App\Models\contact_pro;
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
        // User::factory()->create([
        //     'name' => 'Super Admin',
        //     'email' => 'superadmin@example.com',
        //     'role' => 0,
        //     'phone' => '123456789',
        //     'terms' => 1,
        //     'password' => 'aszx1234',
        // ]);
        // User::factory()->create([
        //     'name' => 'Customer',
        //     'email' => 'customer@example.com',
        //     'role' => 1,
        //     'phone' => '123456789',
        //     'terms' => 1,
        //     'password' => 'aszx1234',
        // ]);

        // User::factory()->create([
        //     'name' => 'Service Provider',
        //     'email' => 'provider@example.com',
        //     'role' => 2,
        //     'phone' => '123456789',
        //     'terms' => 1,
        //     'password' => 'aszx1234',
        // ]);
        // User::factory()->create([
        //     'name' => 'Sales Reps',
        //     'email' => 'salesreps@example.com',
        //     'role' => 3,
        //     'phone' => '123456789',
        //     'terms' => 1,
        //     'password' => 'aszx1234',
        //     'created_by' => 0,
        //     'client_permission_1' => 1,
        //     'client_permission_2' => 0,
        //     'client_permission_3' => 1,
        //     'assign_permission_1' => 0,
        //     'assign_permission_2' => 1,
        //     'assign_permission_3' => 0,
        // ]);

        // User::factory()->create([
        //     'name' => 'Mike Bird',
        //     'email' => 'mike@example.com',
        //     'role' => 3,
        //     'phone' => '123456789',
        //     'terms' => 1,
        //     'password' => 'aszx1234',
        //     'created_by' => 0,
        //     'client_permission_1' => 1,
        //     'client_permission_2' => 0,
        //     'client_permission_3' => 1,
        //     'assign_permission_1' => 0,
        //     'assign_permission_2' => 1,
        //     'assign_permission_3' => 0,
        // ]);

        // User::factory()->create([
        //     'name' => 'Brittany Spurlock',
        //     'email' => 'brittany@example.com',
        //     'role' => 3,
        //     'phone' => '123456789',
        //     'terms' => 1,
        //     'password' => 'aszx1234',
        //     'created_by' => 0,
        //     'client_permission_1' => 1,
        //     'client_permission_2' => 0,
        //     'client_permission_3' => 1,
        //     'assign_permission_1' => 0,
        //     'assign_permission_2' => 1,
        //     'assign_permission_3' => 0,
        // ]);

        // User::factory()->create([
        //     'name' => 'Jami Bird',
        //     'email' => 'jami@example.com',
        //     'role' => 3,
        //     'phone' => '123456789',
        //     'terms' => 1,
        //     'password' => 'aszx1234',
        //     'created_by' => 0,
        //     'client_permission_1' => 1,
        //     'client_permission_2' => 0,
        //     'client_permission_3' => 1,
        //     'assign_permission_1' => 0,
        //     'assign_permission_2' => 1,
        //     'assign_permission_3' => 0,
        // ]);

        // User::factory()->create([
        //     'name' => 'Tabbetha Sells',
        //     'email' => 'tabbethae@example.com',
        //     'role' => 3,
        //     'phone' => '123456789',
        //     'terms' => 1,
        //     'password' => 'aszx1234',
        //     'created_by' => 0,
        //     'client_permission_1' => 1,
        //     'client_permission_2' => 0,
        //     'client_permission_3' => 1,
        //     'assign_permission_1' => 0,
        //     'assign_permission_2' => 1,
        //     'assign_permission_3' => 0,
        // ]);
        contact_pro::factory()->create([
            'customer_id' => 1,
            'provider_id'=> 3,
            'deal_id'=> 2,
            'subject'=> '',
            'text'=> 'Hi seeder',
            'type'=> 'sms_pro',
            'read'=> false,
            'by_service'=> true,
        ]);
    }
}