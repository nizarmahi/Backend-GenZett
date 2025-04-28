<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class ReservationMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reservationMember = [
            ['reservationId' => 1, 'membershipId' => 1],
            ['reservationId' => 2, 'membershipId' => 2],
            ['reservationId' => 3, 'membershipId' => 3],
            ['reservationId' => 4, 'membershipId' => 4],
            ['reservationId' => 5, 'membershipId' => 5],
        ];

        foreach ($reservationMember as $res) {
            DB::table('reservation_members')->insert([
                ...$res,
                'created_at' => now(),
            ]);
        }
    }
}
