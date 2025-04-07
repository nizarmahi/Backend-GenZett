<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SportSeeder extends Seeder
{
    public function run()
    {
        DB::table('sports')->insert([
            ['sportId' => 1, 'sportName' => 'badminton', 'description' => 'Olahraga futsal 5v5', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['sportId' => 2, 'sportName' => 'basket', 'description' => 'Olahraga bola basket', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
}
