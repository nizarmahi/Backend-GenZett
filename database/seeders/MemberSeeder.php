<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $memberships = [
            ['name' => 'Silver Plan', 'description' => '1 bulan akses', 'discount' => 10.00, 'weeks' => 4],
            ['name' => 'Gold Plan', 'description' => '2 bulan akses', 'discount' => 15.00, 'weeks' => 8],
            ['name' => 'Platinum Plan', 'description' => '3 bulan akses', 'discount' => 25.00, 'weeks' => 12],
            ['name' => 'Student Plan', 'description' => 'Diskon mahasiswa', 'discount' => 80.00, 'weeks' => 4],
            ['name' => 'Weekend Plan', 'description' => 'Akses weekend', 'discount' => 5.00, 'weeks' => 4],
        ];

        for ($index = 0; $index < count($memberships); $index++) {
            foreach ($memberships as $membership) {
                DB::table('memberships')->insert([
                    'locationId' => $index + 1,
                    'sportId' => $index + 1,
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
