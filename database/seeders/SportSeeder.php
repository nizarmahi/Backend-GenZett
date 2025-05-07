<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SportSeeder extends Seeder
{
    public function run(): void
    {
        $sports = [
            ['sportName' => 'Futsal', 'description' => 'Olahraga futsal'],
            ['sportName' => 'Basket', 'description' => 'Olahraga basket'],
            ['sportName' => 'Badminton', 'description' => 'Olahraga badminton'],
            ['sportName' => 'Tennis', 'description' => 'Olahraga tennis'],
            ['sportName' => 'Voli', 'description' => 'Olahraga volleyball'],
        ];

        foreach ($sports as $sport) {
            DB::table('sports')->insert([
                ...$sport,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

}
