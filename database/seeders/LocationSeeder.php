<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['locationName' => 'Lapangan A', 'description' => 'Lokasi pusat kota', 'locationPath' => 'lokasi1.jpg'],
            ['locationName' => 'Lapangan B', 'description' => 'Dekat taman', 'locationPath' => 'lokasi2.jpg'],
            ['locationName' => 'Lapangan C', 'description' => 'Samping kampus', 'locationPath' => 'lokasi3.jpg'],
            ['locationName' => 'Lapangan D', 'description' => 'Dekat stasiun', 'locationPath' => 'lokasi4.jpg'],
            ['locationName' => 'Lapangan E', 'description' => 'Daerah pinggiran', 'locationPath' => 'lokasi5.jpg'],
        ];

        foreach ($locations as $loc) {
            DB::table('locations')->insert([
                ...$loc,
                'created_at' => now(),
            ]);
        }
    }
}
