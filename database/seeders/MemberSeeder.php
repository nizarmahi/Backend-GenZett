<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $memberships = [
            ['name' => 'Silver Plan', 'description' => '1 bulan akses', 'discount' => 15.00, 'weeks' => 4],
            ['name' => 'Gold Plan', 'description' => '2 bulan akses', 'discount' => 20.00, 'weeks' => 8],
            ['name' => 'Platinum Plan', 'description' => '3 bulan akses', 'discount' => 20.00, 'weeks' => 12],
            ['name' => 'Student Plan', 'description' => 'Diskon mahasiswa', 'discount' => 30.00, 'weeks' => 4],
            ['name' => 'Weekend Plan', 'description' => 'Akses weekend', 'discount' => 10.00, 'weeks' => 4],
        ];

        // Ambil kombinasi unik location_id dan sport_id dari tabel fields
        $fieldCombinations = DB::table('fields')
            ->select('locationId', 'sportId')
            ->distinct()
            ->get();

        foreach ($fieldCombinations as $combo) {
            foreach ($memberships as $membership) {
                DB::table('memberships')->insert([
                    'locationId' => $combo->locationId,
                    'sportId' => $combo->sportId,
                    'name' => $membership['name'],
                    'description' => $membership['description'],
                    'discount' => $membership['discount'],
                    'weeks' => $membership['weeks'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
