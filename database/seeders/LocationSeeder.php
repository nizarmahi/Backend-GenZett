<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['locationName' => 'Lowokwaru', 'description' => 'Daerah kampus dan pemukiman', 'locationPath' => 'lowokwaru.jpg', 'address' => 'Jl. Soekarno Hatta, Malang'],
            ['locationName' => 'Klojen', 'description' => 'Pusat kota Malang', 'locationPath' => 'klojen.jpg', 'address' => 'Jl. Ijen, Malang'],
            ['locationName' => 'Sukun', 'description' => 'Daerah industri dan hunian', 'locationPath' => 'sukun.jpg', 'address' => 'Jl. S. Supriadi, Malang'],
            ['locationName' => 'Blimbing', 'description' => 'Wilayah bisnis dan transportasi', 'locationPath' => 'blimbing.jpg', 'address' => 'Jl. LA Sucipto, Malang'],
            ['locationName' => 'Kedungkandang', 'description' => 'Wilayah pinggiran dengan lahan luas', 'locationPath' => 'kedungkandang.jpg', 'address' => 'Jl. Ki Ageng Gribig, Malang'],
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
