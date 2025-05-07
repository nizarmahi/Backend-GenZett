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
            ['locationName' => 'Lapangan A', 'description' => 'Lokasi pusat kota', 'locationPath' => 'lokasi1.jpg', 'address' => 'Jalan Merdeka No. 1'],
            ['locationName' => 'Lapangan B', 'description' => 'Dekat taman', 'locationPath' => 'lokasi2.jpg', 'address' => 'Jalan Merdeka No. 1'],
            ['locationName' => 'Lapangan C', 'description' => 'Samping kampus', 'locationPath' => 'lokasi3.jpg', 'address' => 'Jalan Merdeka No. 1'],
            ['locationName' => 'Lapangan D', 'description' => 'Dekat stasiun', 'locationPath' => 'lokasi4.jpg', 'address' => 'Jalan Merdeka No. 1'],
            ['locationName' => 'Lapangan E', 'description' => 'Daerah pinggiran', 'locationPath' => 'lokasi5.jpg', 'address' => 'Jalan Merdeka No. 1'],
        ];

        foreach ($locations as $loc) {
            DB::table('locations')->insert([
                ...$loc,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
