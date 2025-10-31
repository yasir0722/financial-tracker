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
        $banks = [
            ['name' => 'Bank Islam'],
            ['name' => 'Maybank'],
            ['name' => 'CIMB Bank'],
            ['name' => 'Public Bank'],
        ];

        DB::table('banks')->insert($banks);
    }
}
