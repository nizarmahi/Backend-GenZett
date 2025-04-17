<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FieldSeeder extends Seeder
{
    public function run(): void
    {
        $fields = [
            ['locationId' => 1, 'sportId' => 1, 'name' => 'Lapangan Futsal 1', 'description' => 'Indoor'],
            ['locationId' => 2, 'sportId' => 2, 'name' => 'Lapangan Basket 1', 'description' => 'Outdoor'],
            ['locationId' => 3, 'sportId' => 3, 'name' => 'Lapangan Badminton 1', 'description' => 'Indoor'],
            ['locationId' => 4, 'sportId' => 4, 'name' => 'Lapangan Tennis 1', 'description' => 'Outdoor'],
            ['locationId' => 5, 'sportId' => 5, 'name' => 'Lapangan Volley 1', 'description' => 'Indoor'],
        ];

        foreach ($fields as $field) {
            DB::table('fields')->insert([
                ...$field,
                'created_at' => now(),
            ]);
        }
    }

}
