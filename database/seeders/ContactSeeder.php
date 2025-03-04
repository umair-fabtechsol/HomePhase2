<?php

namespace Database\Seeders;

use App\Models\contact_pro;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        contact_pro::create([
            'customer_id' => 1,
            'provider_id' => 3,
            'deal_id' => 2,
            'subject' => '',
            'text' => 'Hi seeder',
            'type' => 'sms_pro',
            'read' => false,
            'by_service' => true,
        ]);
    }
}
