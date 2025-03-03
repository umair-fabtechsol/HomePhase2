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
                'customer_id' => 3,
                'deal_id' => 13,
                'total_amount' => 50,
                'status' => 'pending',
                'notes' => 'test',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'customer_id' => 3,
                'deal_id' => 14,
                'total_amount' => 60,
                'status' => 'pending',
                'notes' => 'test order 2',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'customer_id' => 3,
                'deal_id' => 15,
                'total_amount' => 60,
                'status' => 'pending',
                'notes' => 'test order 2',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
        
    }
}