<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $reservations = [];
        $type = ['reguler', 'membership'];
        $statusReservation = ['pending', 'dp', 'complete', 'fail'];
        $name = [
            'Regular Match', 'Tournament', 'Training Session', 'Friendly Game',
            'League Match', 'Championship', 'Practice', 'Tryout',
            'School Event', 'Company Outing', 'Birthday Party', 'Charity Event'
        ];

        for ($i = 1; $i <= 50; $i++) {
            $userId = rand(6, 10);
            $status = $statusReservation[array_rand($statusReservation)];
            $event = $name[array_rand($name)] . ' ' . ($i % 10 + 1);
            $total = rand(10, 25) * 10000;
            if ($i % 20 == 0) {
                $typeReservation = $type[1];
            } else {
                $typeReservation = $type[0];
            }

            $reservations[] = [
                'userId' => $userId,
                'name' => $event,
                'paymentStatus' => $status,
                'paymentType' => $typeReservation,
                'total' => $total,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        DB::table('reservations')->insert($reservations);
    }
}
