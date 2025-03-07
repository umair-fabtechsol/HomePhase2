<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        DB::table('orders')->insert([
            [
                'provider_id' => 3,
                'customer_id' => 2,
                'deal_id' => 1,
                'total_amount' => 20,
                'status' => 'pending',
                'notes' => 'test',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'provider_id' => 3,
                'customer_id' => 11,
                'deal_id' => 14,
                'total_amount' => 60,
                'status' => 'pending',
                'notes' => 'test order 2',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'provider_id' => 24,
                'customer_id' => 20,
                'deal_id' => 16,
                'total_amount' => 60,
                'status' => 'pending',
                'notes' => 'test order 2',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
        
    }
}