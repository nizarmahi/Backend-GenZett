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
            // Sport IDs:1
            ['locationId' => 1, 'sportId' => 1, 'name' => 'Lapangan Futsal 1', 'description' => 'Indoor'],
            ['locationId' => 1, 'sportId' => 1, 'name' => 'Lapangan Futsal 2', 'description' => 'Indoor'],
            ['locationId' => 1, 'sportId' => 1, 'name' => 'Lapangan Futsal 3', 'description' => 'Indoor'],
            ['locationId' => 1, 'sportId' => 1, 'name' => 'Lapangan Futsal 4', 'description' => 'Indoor'],
            ['locationId' => 2, 'sportId' => 1, 'name' => 'Lapangan Futsal 1', 'description' => 'Indoor'],
            ['locationId' => 2, 'sportId' => 1, 'name' => 'Lapangan Futsal 2', 'description' => 'Indoor'],
            ['locationId' => 3, 'sportId' => 1, 'name' => 'Lapangan Futsal 1', 'description' => 'Indoor'],

            // Sport IDs:2
            ['locationId' => 1, 'sportId' => 2, 'name' => 'Lapangan Badminton 1', 'description' => 'Indoor'],
            ['locationId' => 1, 'sportId' => 2, 'name' => 'Lapangan Badminton 2', 'description' => 'Indoor'],
            ['locationId' => 2, 'sportId' => 2, 'name' => 'Lapangan Badminton 1', 'description' => 'Indoor'],
            ['locationId' => 2, 'sportId' => 2, 'name' => 'Lapangan Badminton 2', 'description' => 'Indoor'],
            ['locationId' => 2, 'sportId' => 2, 'name' => 'Lapangan Badminton 3', 'description' => 'Indoor'],
            ['locationId' => 2, 'sportId' => 2, 'name' => 'Lapangan Badminton 4', 'description' => 'Indoor'],
            ['locationId' => 4, 'sportId' => 2, 'name' => 'Lapangan Badminton 1', 'description' => 'Indoor'],
            ['locationId' => 4, 'sportId' => 2, 'name' => 'Lapangan Badminton 2', 'description' => 'Indoor'],
            ['locationId' => 4, 'sportId' => 2, 'name' => 'Lapangan Badminton 3', 'description' => 'Indoor'],

            // Sport IDs:3
            ['locationId' => 3, 'sportId' => 3, 'name' => 'Lapangan Basket 2', 'description' => 'Indoor'],
            ['locationId' => 3, 'sportId' => 3, 'name' => 'Lapangan Basket 1', 'description' => 'Indoor'],
            ['locationId' => 3, 'sportId' => 3, 'name' => 'Lapangan Basket 2', 'description' => 'Indoor'],
            ['locationId' => 4, 'sportId' => 3, 'name' => 'Lapangan Basket 1', 'description' => 'Indoor'],
            ['locationId' => 4, 'sportId' => 3, 'name' => 'Lapangan Basket 1', 'description' => 'Indoor'],
            ['locationId' => 4, 'sportId' => 3, 'name' => 'Lapangan Basket 2', 'description' => 'Indoor'],
            ['locationId' => 5, 'sportId' => 3, 'name' => 'Lapangan Basket 1', 'description' => 'Indoor'],
            ['locationId' => 5, 'sportId' => 3, 'name' => 'Lapangan Basket 2', 'description' => 'Indoor'],
            // Sport IDs:4
            ['locationId' => 1, 'sportId' => 4, 'name' => 'Lapangan Voli 1', 'description' => 'Outdoor'],
            ['locationId' => 1, 'sportId' => 4, 'name' => 'Lapangan Voli 2', 'description' => 'Outdoor'],
            ['locationId' => 3, 'sportId' => 4, 'name' => 'Lapangan Voli 1', 'description' => 'Outdoor'],
            ['locationId' => 3, 'sportId' => 4, 'name' => 'Lapangan Voli 2', 'description' => 'Outdoor'],
            ['locationId' => 5, 'sportId' => 4, 'name' => 'Lapangan Voli 1', 'description' => 'Outdoor'],

            // Sport IDs:5
            ['locationId' => 4, 'sportId' => 5, 'name' => 'Lapangan Tennis 1', 'description' => 'Outdoor'],
            ['locationId' => 4, 'sportId' => 5, 'name' => 'Lapangan Tennis 2', 'description' => 'Outdoor'],
            ['locationId' => 5, 'sportId' => 5, 'name' => 'Lapangan Tennis 1', 'description' => 'Outdoor'],
            ['locationId' => 5, 'sportId' => 5, 'name' => 'Lapangan Tennis 2', 'description' => 'Outdoor'],
            ['locationId' => 5, 'sportId' => 5, 'name' => 'Lapangan Tennis 3', 'description' => 'Outdoor'],
            ['locationId' => 5, 'sportId' => 5, 'name' => 'Lapangan Tennis 4', 'description' => 'Outdoor'],
        ];

        foreach ($fields as $field) {
            DB::table('fields')->insert([
                ...$field,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

}
