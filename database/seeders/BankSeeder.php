<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing banks to have type = true
        DB::table('banks')->whereIn('name', [
            'Bank Islam', 'Maybank', 'CIMB Bank', 'Public Bank'
        ])->update(['type' => true]);

        // Regular Banks (type = true) - Insert if not exists
        $banks = [
            ['name' => 'Bank Islam', 'type' => true],
            ['name' => 'Maybank', 'type' => true],
            ['name' => 'CIMB Bank', 'type' => true],
            ['name' => 'Public Bank', 'type' => true],
            ['name' => 'Bank Rakyat', 'type' => true],
        ];

        // Other Financial Institutions (type = false)
        $otherInstitutions = [
            ['name' => 'Tabung Haji', 'type' => false],
            ['name' => 'ASB', 'type' => false],
            ['name' => 'KWSP', 'type' => false],
        ];

        // Insert banks that don't exist
        foreach (array_merge($banks, $otherInstitutions) as $bank) {
            DB::table('banks')->updateOrInsert(
                ['name' => $bank['name']], 
                array_merge($bank, [
                    'created_at' => now(), 
                    'updated_at' => now()
                ])
            );
        }
    }
}
