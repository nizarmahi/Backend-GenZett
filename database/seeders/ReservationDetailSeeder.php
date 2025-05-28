<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservationDetailSeeder extends Seeder
{
    public function run(): void
    {
        $details = [
            [
                'reservationId' => 1,
                'fieldId' => 1,
                'timeId' => 1,
                'date' => Carbon::now()->format('Y-m-d'),
            ],
            [
                'reservationId' => 1,
                'fieldId' => 1,
                'timeId' => 2,
                'date' => Carbon::now()->format('Y-m-d'),
            ],
            [
                'reservationId' => 2,
                'fieldId' => 2,
                'timeId' => 2,
                'date' => Carbon::now()->format('Y-m-d'),
            ],
            [
                'reservationId' => 3,
                'fieldId' => 3,
                'timeId' => 3,
                'date' => Carbon::now()->format('Y-m-d'),
            ],
            [
                'reservationId' => 4,
                'fieldId' => 4,
                'timeId' => 4,
                'date' => Carbon::now()->format('Y-m-d'),
            ],
            [
                'reservationId' => 5,
                'fieldId' => 5,
                'timeId' => 5,
                'date' => Carbon::now()->format('Y-m-d'),
            ],
        ];

        foreach ($details as $detail) {
            DB::table('reservation_details')->insert([
                ...$detail,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
