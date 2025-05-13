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
            [
                'sportName' => 'Futsal',
                'description' => 'Olahraga mirip sepak bola, dimainkan di dalam ruangan.'
            ],
            [
                'sportName' => 'Badminton',
                'description' => 'Olahraga raket yang dimainkan oleh dua atau empat orang.'
            ],
            [
                'sportName' => 'Basketball',
                'description' => 'Olahraga tim yang bertujuan memasukkan bola ke keranjang.'
            ],
            [
                'sportName' => 'Volleyball',
                'description' => 'Olahraga memukul bola melewati net dengan tangan.'
            ],
            [
                'sportName' => 'Tennis',
                'description' => 'Olahraga raket satu lawan satu atau ganda.'
            ],
            [
                'sportName' => 'Sepak Bola',
                'description' => 'Olahraga paling populer dengan 11 pemain tiap tim.'
            ],
            [
                'sportName' => 'Handball',
                'description' => 'Olahraga cepat dengan bola tangan.'
            ],
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
