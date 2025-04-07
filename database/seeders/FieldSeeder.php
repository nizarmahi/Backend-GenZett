<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FieldSeeder extends Seeder
{
    public function run()
    {
        DB::table('fields')->insert([
            ['fieldId' => 1, 'locationId' => 1, 'sportId' => 1, 'name' => 'Futsal Court', 'description' => 'Lapangan futsal standar nasional', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['fieldId' => 2, 'locationId' => 2, 'sportId' => 2, 'name' => 'Basketball Court', 'description' => 'Lapangan basket full court', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
}
