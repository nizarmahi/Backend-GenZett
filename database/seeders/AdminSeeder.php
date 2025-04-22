<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            ['userId' => 1, 'locationId' => 1],
            ['userId' => 2, 'locationId' => 2],
            ['userId' => 3, 'locationId' => 3],
            ['userId' => 4, 'locationId' => 4],
            ['userId' => 5, 'locationId' => 5],
        ];

        foreach ($admins as $admin) {
            DB::table('admins')->insert([
                ...$admin,
                'created_at' => now(),
            ]);
        }
    }

}
