<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LocationSeeder extends Seeder
{
    public function run()
    {
        DB::table('locations')->insert([
            ['locationId' => 1, 'locationName' => 'Lapangan A', 'description' => 'Lapangan indoor dengan fasilitas lengkap', 'locationPath' => 'images/lapangan_a.jpg', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['locationId' => 2, 'locationName' => 'Lapangan B', 'description' => 'Lapangan outdoor dengan rumput sintetis', 'locationPath' => 'images/lapangan_b.jpg', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
}
